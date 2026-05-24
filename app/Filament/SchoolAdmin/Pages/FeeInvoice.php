<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Http\Controllers\FeeReceiptController;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as FormSection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fee Invoice — pick a student (+ optional academic year), then download a
 * printable PDF of their assigned/outstanding fees grouped by fee type.
 * Uses the existing barryvdh/laravel-dompdf and streams the PDF straight
 * back from the action (no web route needed).
 */
class FeeInvoice extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'generate_fee_receipts';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Fees';

    protected static ?string $navigationLabel = 'Fee Invoice';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.school-admin.pages.fee-invoice';

    public function getTitle(): string
    {
        return 'Fee Invoice';
    }

    public function getSubheading(): ?string
    {
        return 'Pick a student and download a printable PDF invoice of their assigned and outstanding fees.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateInvoice')
                ->label('Generate invoice PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->modalHeading('Generate fee invoice')
                ->modalSubmitActionLabel('Download PDF')
                ->form([
                    FormSection::make('Selection')
                        ->columns(2)
                        ->schema([
                            Select::make('student_id')
                                ->label('Student')
                                ->options(fn () => Student::query()
                                    ->orderBy('first_name')
                                    ->limit(1000)
                                    ->get()
                                    ->mapWithKeys(fn (Student $s) => [
                                        $s->id => trim($s->full_name . ' · ' . ($s->admission_number ?? '—')),
                                    ])
                                    ->toArray())
                                ->searchable()
                                ->required(),
                            Select::make('academic_year_id')
                                ->label('Academic Year (optional)')
                                ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                                ->placeholder('All years')
                                ->default(fn () => AcademicYear::query()->where('is_current', true)->value('id')),
                            Toggle::make('outstanding_only')
                                ->label('Outstanding fees only')
                                ->helperText('Off = include paid/waived rows too.')
                                ->default(true),
                        ]),
                ])
                ->action(fn (array $data): StreamedResponse => $this->buildInvoice($data)),
        ];
    }

    protected function buildInvoice(array $data): StreamedResponse
    {
        $student = Student::with(['schoolClass', 'section', 'academicYear'])->find($data['student_id']);

        if (! $student) {
            Notification::make()->title('Student not found')->danger()->send();
            abort(404);
        }

        $query = StudentFee::query()
            ->with(['feeType', 'academicYear'])
            ->where('student_id', $student->id)
            ->orderBy('due_date');

        if (! empty($data['academic_year_id'])) {
            $query->where('academic_year_id', $data['academic_year_id']);
        }

        if (! empty($data['outstanding_only'])) {
            $query->whereIn('status', ['pending', 'partial']);
        }

        $fees = $query->get();

        $totals = [
            'billed'      => (int) $fees->sum('amount_paisas'),
            'fine'        => (int) $fees->sum('fine_paisas'),
            'discount'    => (int) $fees->sum('discount_paisas'),
            'paid'        => (int) $fees->sum('paid_paisas'),
        ];
        $totals['outstanding'] = max(
            0,
            $totals['billed'] + $totals['fine'] - $totals['discount'] - $totals['paid'],
        );

        $tenant = function_exists('tenant') ? tenant() : null;

        $academicYearName = ! empty($data['academic_year_id'])
            ? AcademicYear::find($data['academic_year_id'])?->name
            : null;

        $pdf = Pdf::loadView('pdf.fee-invoice', [
            'student'             => $student,
            'fees'                => $fees,
            'totals'              => $totals,
            'invoiceNumber'       => 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr($student->id, -5)),
            'issuedOn'            => now()->format('d M Y'),
            'academicYearName'    => $academicYearName,
            'schoolName'          => $tenant?->school_name ?? config('app.name', 'School'),
            'schoolMeta'          => [
                'tagline' => optional($tenant)->tagline,
                'address' => optional($tenant)->address,
                'phone'   => optional($tenant)->phone,
                'email'   => optional($tenant)->email,
            ],
            'outstandingInWords'  => FeeReceiptController::numberToWords((int) round($totals['outstanding'] / 100)),
        ])->setPaper('a4');

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $student->full_name);

        $bytes = $pdf->output();

        return response()->streamDownload(
            function () use ($bytes): void {
                echo $bytes;
            },
            "FeeInvoice_{$safeName}.pdf",
            ['Content-Type' => 'application/pdf', 'Content-Length' => (string) strlen($bytes)],
        );
    }
}
