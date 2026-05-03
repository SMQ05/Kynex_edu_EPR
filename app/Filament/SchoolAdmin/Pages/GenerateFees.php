<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\FeeMaster;
use App\Models\Tenant\FeeType;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use App\Services\FeesService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as FormSection;
use Illuminate\Support\Facades\Log;

/**
 * Admin-driven fee invoicing dashboard.
 *
 * Three header actions cover the operating lifecycle:
 *   1. Roll out monthly fees — generates StudentFee rows from FeeMaster
 *      definitions for a given class/section/month.
 *   2. Add one-time fee — creates a single StudentFee row for one
 *      student (e.g. exam re-take, library fine, lab equipment loss).
 *   3. Apply late fees — adds a per-day fine to all overdue invoices.
 *
 * The page itself shows summary tiles so the operator can see the
 * impact at a glance.
 */
class GenerateFees extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'create_fee_structure';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Generate Fees';

    protected string $view = 'filament.school-admin.pages.generate-fees';

    public function getTitle(): string
    {
        return 'Generate Fees';
    }

    /**
     * Summary tiles for the page body — totals across the active year.
     *
     * @return array<string, int|string>
     */
    public function summary(): array
    {
        $academicYearId = AcademicYear::query()->where('is_current', true)->value('id');

        $base = StudentFee::query()
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        $unpaid = (clone $base)->whereIn('status', ['pending', 'partial'])->count();
        $paid = (clone $base)->where('status', 'paid')->count();
        $waived = (clone $base)->where('status', 'waived')->count();
        $totalOutstanding = (clone $base)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('coalesce(sum(amount_paisas - paid_paisas + fine_paisas - discount_paisas), 0) as total')
            ->value('total');

        $masters = FeeMaster::query()
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('is_active', true)
            ->count();

        return [
            'unpaid_count' => (int) $unpaid,
            'paid_count' => (int) $paid,
            'waived_count' => (int) $waived,
            'outstanding_pkr' => 'PKR ' . number_format(((int) $totalOutstanding) / 100, 2),
            'fee_masters' => (int) $masters,
            'academic_year' => AcademicYear::query()->where('is_current', true)->value('name') ?? '—',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rolloutMonthly')
                ->label('Roll out monthly fees')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->modalHeading('Generate monthly fees from Fee Structure')
                ->modalDescription('Bulk-creates a StudentFee row for every enrolled student in the chosen class × month, based on Fee Structure definitions. Idempotent: existing rows for the same month are skipped.')
                ->form([
                    FormSection::make('Target')
                        ->columns(2)
                        ->schema([
                            Select::make('class_id')
                                ->label('Class')
                                ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive(),
                            Select::make('section_id')
                                ->label('Section (optional)')
                                ->options(fn ($get) => $get('class_id')
                                    ? Section::where('class_id', $get('class_id'))->orderBy('name')->pluck('name', 'id')
                                    : [])
                                ->searchable(),
                            Select::make('academic_year_id')
                                ->label('Academic Year')
                                ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                                ->required()
                                ->default(fn () => AcademicYear::query()->where('is_current', true)->value('id')),
                            TextInput::make('month')
                                ->label('Month (YYYY-MM)')
                                ->placeholder(now()->format('Y-m'))
                                ->default(now()->format('Y-m'))
                                ->required()
                                ->regex('/^\d{4}-\d{2}$/')
                                ->helperText('Use the bill month format, e.g. 2026-05.'),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $svc = app(FeesService::class);
                        $result = $svc->generateStudentFees(
                            classId: $data['class_id'],
                            sectionId: $data['section_id'] ?: null,
                            academicYearId: $data['academic_year_id'],
                            month: $data['month'],
                        );
                        Notification::make()
                            ->title("Generated {$result['generated']} fee invoices")
                            ->body("Skipped {$result['skipped']} (already existed for this month).")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::warning('GenerateFees rolloutMonthly failed', ['error' => $e->getMessage()]);
                        Notification::make()
                            ->title('Could not generate fees')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('oneTimeFee')
                ->label('One-time fee')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning')
                ->modalHeading('Add a one-time fee for a student')
                ->modalDescription('Use for ad-hoc charges: exam re-take fees, library fines, lab equipment loss, etc.')
                ->form([
                    FormSection::make('Charge')
                        ->columns(2)
                        ->schema([
                            Select::make('student_id')
                                ->label('Student')
                                ->options(fn () => Student::query()
                                    ->where('status', 'enrolled')
                                    ->orderBy('first_name')
                                    ->limit(500)
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [
                                        $s->id => trim($s->full_name . ' · ' . ($s->admission_number ?? '—')),
                                    ])
                                    ->toArray())
                                ->required()
                                ->searchable(),
                            Select::make('fee_type_id')
                                ->label('Fee Type (one-time)')
                                ->options(fn () => FeeType::where('is_recurring', false)->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->helperText('Only one-time fee types are listed.'),
                            TextInput::make('amount_pkr')
                                ->label('Amount (PKR)')
                                ->numeric()
                                ->minValue(0)
                                ->step('0.01')
                                ->prefix('PKR')
                                ->required(),
                            DatePicker::make('due_date')
                                ->label('Due Date')
                                ->default(now()->addDays(14))
                                ->required(),
                            Select::make('academic_year_id')
                                ->label('Academic Year')
                                ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                                ->required()
                                ->default(fn () => AcademicYear::query()->where('is_current', true)->value('id')),
                            TextInput::make('remarks')
                                ->label('Note (optional)')
                                ->maxLength(255),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $rupees = (float) $data['amount_pkr'];
                        $paisas = (int) round($rupees * 100);
                        $dueDate = Carbon::parse($data['due_date']);

                        StudentFee::create([
                            'student_id'       => $data['student_id'],
                            'fee_type_id'      => $data['fee_type_id'],
                            'academic_year_id' => $data['academic_year_id'],
                            'month'            => $dueDate->format('Y-m'),
                            'amount_paisas'    => $paisas,
                            'discount_paisas'  => 0,
                            'fine_paisas'      => 0,
                            'paid_paisas'      => 0,
                            'status'           => 'pending',
                            'due_date'         => $dueDate->toDateString(),
                            'remarks'          => $data['remarks'] ?? null,
                        ]);

                        Notification::make()
                            ->title('One-time fee added')
                            ->body('PKR ' . number_format($rupees, 2) . ' due on ' . $dueDate->format('d M Y'))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::warning('GenerateFees oneTimeFee failed', ['error' => $e->getMessage()]);
                        Notification::make()
                            ->title('Could not add fee')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('oneTimeFeeForAll')
                ->label('One-time fee · whole class / school')
                ->icon('heroicon-o-rectangle-group')
                ->color('warning')
                ->modalHeading('Charge a one-time fee to a group of students')
                ->modalDescription('Generates a single one-time invoice for every selected student in one go. Choose ALL students or filter by class / section.')
                ->form([
                    FormSection::make('Target group')
                        ->columns(2)
                        ->schema([
                            Select::make('scope')
                                ->label('Apply to')
                                ->options([
                                    'all'      => 'All enrolled students (whole school)',
                                    'class'    => 'A specific class',
                                    'section'  => 'A specific section',
                                ])
                                ->default('class')
                                ->required()
                                ->reactive(),
                            Select::make('class_id')
                                ->label('Class')
                                ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                                ->visible(fn ($get) => in_array($get('scope'), ['class', 'section'], true))
                                ->required(fn ($get) => in_array($get('scope'), ['class', 'section'], true))
                                ->reactive(),
                            Select::make('section_id')
                                ->label('Section')
                                ->options(fn ($get) => $get('class_id')
                                    ? Section::where('class_id', $get('class_id'))->orderBy('name')->pluck('name', 'id')
                                    : [])
                                ->visible(fn ($get) => $get('scope') === 'section')
                                ->required(fn ($get) => $get('scope') === 'section'),
                        ]),
                    FormSection::make('Charge')
                        ->columns(2)
                        ->schema([
                            Select::make('fee_type_id')
                                ->label('Fee Type (one-time)')
                                ->options(fn () => FeeType::where('is_recurring', false)->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->helperText('Only one-time fee types are shown. Add new ones from Fees → Fee Types.'),
                            TextInput::make('amount_pkr')
                                ->label('Amount per student (PKR)')
                                ->numeric()->minValue(0)->step('0.01')->prefix('PKR')->required(),
                            DatePicker::make('due_date')
                                ->label('Due Date')
                                ->default(now()->addDays(14))
                                ->required(),
                            Select::make('academic_year_id')
                                ->label('Academic Year')
                                ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                                ->required()
                                ->default(fn () => AcademicYear::query()->where('is_current', true)->value('id')),
                            TextInput::make('remarks')
                                ->label('Note (optional)')
                                ->maxLength(255)
                                ->columnSpan(2),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $rupees = (float) $data['amount_pkr'];
                        $paisas = (int) round($rupees * 100);
                        $dueDate = \Carbon\Carbon::parse($data['due_date']);
                        $month = $dueDate->format('Y-m');

                        $q = Student::query()->where('status', 'enrolled');
                        if (($data['scope'] ?? 'class') === 'class' && ! empty($data['class_id'])) {
                            $q->where('class_id', $data['class_id']);
                        } elseif (($data['scope'] ?? '') === 'section' && ! empty($data['section_id'])) {
                            $q->where('section_id', $data['section_id']);
                        }
                        $students = $q->get();

                        $created = 0;
                        $skipped = 0;
                        foreach ($students as $student) {
                            // Idempotency: if a row already exists for the
                            // same student × fee_type × month, skip.
                            $exists = StudentFee::where('student_id', $student->id)
                                ->where('fee_type_id', $data['fee_type_id'])
                                ->where('month', $month)
                                ->exists();
                            if ($exists) { $skipped++; continue; }

                            StudentFee::create([
                                'student_id'       => $student->id,
                                'fee_type_id'      => $data['fee_type_id'],
                                'academic_year_id' => $data['academic_year_id'],
                                'month'            => $month,
                                'amount_paisas'    => $paisas,
                                'discount_paisas'  => 0,
                                'fine_paisas'      => 0,
                                'paid_paisas'      => 0,
                                'status'           => 'pending',
                                'due_date'         => $dueDate->toDateString(),
                                'remarks'          => $data['remarks'] ?? null,
                            ]);
                            $created++;
                        }

                        Notification::make()
                            ->title("Created $created invoices · skipped $skipped")
                            ->body('Each student now owes PKR ' . number_format($rupees, 2) . ' due ' . $dueDate->format('d M Y') . '.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('GenerateFees oneTimeFeeForAll failed', ['error' => $e->getMessage()]);
                        Notification::make()->title('Bulk fee failed')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('applyLateFees')
                ->label('Apply late fees')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->modalHeading('Apply late fees to overdue invoices')
                ->modalDescription('Adds a per-day fine to every unpaid/partial invoice that\'s past its due date. Existing fines are recalculated.')
                ->form([
                    FormSection::make('Late-fee policy')
                        ->columns(2)
                        ->schema([
                            TextInput::make('fine_pkr_per_day')
                                ->label('Fine per day (PKR)')
                                ->numeric()
                                ->minValue(0)
                                ->step('0.01')
                                ->prefix('PKR')
                                ->required()
                                ->default(50),
                            TextInput::make('cap_pkr')
                                ->label('Maximum fine cap (PKR, blank = no cap)')
                                ->numeric()
                                ->minValue(0)
                                ->step('0.01')
                                ->prefix('PKR'),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $perDay = (int) round(((float) $data['fine_pkr_per_day']) * 100);
                        $cap = isset($data['cap_pkr']) && $data['cap_pkr'] !== null && $data['cap_pkr'] !== ''
                            ? (int) round(((float) $data['cap_pkr']) * 100)
                            : null;

                        $svc = app(FeesService::class);
                        $count = $svc->applyLateFees($perDay, $cap);

                        Notification::make()
                            ->title("Late fees applied to {$count} invoices")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::warning('GenerateFees applyLateFees failed', ['error' => $e->getMessage()]);
                        Notification::make()
                            ->title('Could not apply late fees')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
