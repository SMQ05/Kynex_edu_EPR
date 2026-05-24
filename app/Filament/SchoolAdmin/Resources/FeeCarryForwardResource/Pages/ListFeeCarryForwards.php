<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FeeCarryForwardResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeCarryForwardResource;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\FeeCarryForward;
use App\Models\Tenant\FeeGroup;
use App\Models\Tenant\FeeType;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section as FormSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListFeeCarryForwards extends ListRecords
{
    protected static string $resource = FeeCarryForwardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('carryForward')
                ->label('Carry forward outstanding')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->modalHeading('Carry forward unpaid balances')
                ->modalDescription('Computes each student\'s outstanding balance in the source year and creates a single carry-forward fee invoice in the target year. Already carried-forward students are skipped.')
                ->modalSubmitActionLabel('Carry forward')
                ->form([
                    FormSection::make('Years')
                        ->columns(2)
                        ->schema([
                            Select::make('from_academic_year_id')
                                ->label('From academic year')
                                ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                                ->required(),
                            Select::make('to_academic_year_id')
                                ->label('To academic year')
                                ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                                ->default(fn () => AcademicYear::query()->where('is_current', true)->value('id'))
                                ->required(),
                        ]),
                    FormSection::make('Scope & invoice')
                        ->columns(2)
                        ->schema([
                            Select::make('scope')
                                ->label('Apply to')
                                ->options([
                                    'all'   => 'All students with an outstanding balance',
                                    'class' => 'A specific class',
                                    'one'   => 'A single student',
                                ])
                                ->default('all')
                                ->required()
                                ->reactive(),
                            Select::make('class_id')
                                ->label('Class')
                                ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('scope') === 'class')
                                ->required(fn ($get) => $get('scope') === 'class'),
                            Select::make('student_id')
                                ->label('Student')
                                ->options(fn () => Student::query()->orderBy('first_name')->limit(1000)->get()
                                    ->mapWithKeys(fn (Student $s) => [$s->id => trim($s->full_name . ' · ' . ($s->admission_number ?? '—'))])
                                    ->toArray())
                                ->searchable()
                                ->visible(fn ($get) => $get('scope') === 'one')
                                ->required(fn ($get) => $get('scope') === 'one'),
                            DatePicker::make('due_date')
                                ->label('Due date for carried invoice')
                                ->default(now()->addDays(30))
                                ->required(),
                            Textarea::make('note')->rows(2)->columnSpanFull(),
                        ]),
                ])
                ->action(fn (array $data) => $this->runCarryForward($data)),
        ];
    }

    protected function runCarryForward(array $data): void
    {
        if (($data['from_academic_year_id'] ?? null) === ($data['to_academic_year_id'] ?? null)) {
            Notification::make()->title('Source and target years must differ')->danger()->send();

            return;
        }

        try {
            // A dedicated fee type so the carried StudentFee FK is satisfied
            // and these invoices are easy to identify/report on.
            $group = FeeGroup::firstOrCreate(['name' => 'Carry Forward'], ['description' => 'Balances carried over from a previous academic year.']);
            $feeType = FeeType::firstOrCreate(
                ['fee_group_id' => $group->id, 'name' => 'Previous Year Dues'],
                ['is_recurring' => false],
            );

            $fromYear = $data['from_academic_year_id'];
            $toYear = $data['to_academic_year_id'];
            $dueDate = Carbon::parse($data['due_date']);

            // Students who still owe in the source year.
            $rows = StudentFee::query()
                ->where('academic_year_id', $fromYear)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('student_id, coalesce(sum(amount_paisas + fine_paisas - discount_paisas - paid_paisas), 0) as outstanding')
                ->groupBy('student_id')
                ->having(DB::raw('coalesce(sum(amount_paisas + fine_paisas - discount_paisas - paid_paisas), 0)'), '>', 0);

            if (($data['scope'] ?? 'all') === 'one') {
                $rows->where('student_id', $data['student_id']);
            } elseif (($data['scope'] ?? '') === 'class') {
                $classStudentIds = Student::where('class_id', $data['class_id'])->pluck('id');
                $rows->whereIn('student_id', $classStudentIds);
            }

            $rows = $rows->get();

            $created = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $outstanding = (int) $row->outstanding;
                if ($outstanding <= 0) {
                    $skipped++;
                    continue;
                }

                // Idempotency: don't carry the same from→to pair twice.
                $exists = FeeCarryForward::query()
                    ->where('student_id', $row->student_id)
                    ->where('from_academic_year_id', $fromYear)
                    ->where('to_academic_year_id', $toYear)
                    ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                DB::transaction(function () use ($row, $outstanding, $feeType, $toYear, $fromYear, $dueDate, $data, &$created): void {
                    $studentFee = StudentFee::create([
                        'student_id'       => $row->student_id,
                        'fee_type_id'      => $feeType->id,
                        'academic_year_id' => $toYear,
                        'month'            => $dueDate->format('Y-m'),
                        'amount_paisas'    => $outstanding,
                        'discount_paisas'  => 0,
                        'fine_paisas'      => 0,
                        'paid_paisas'      => 0,
                        'status'           => 'pending',
                        'due_date'         => $dueDate->toDateString(),
                        'remarks'          => 'Carried forward from previous year.',
                    ]);

                    FeeCarryForward::create([
                        'student_id'            => $row->student_id,
                        'from_academic_year_id' => $fromYear,
                        'to_academic_year_id'   => $toYear,
                        'student_fee_id'        => $studentFee->id,
                        'amount_paisas'         => $outstanding,
                        'note'                  => $data['note'] ?? null,
                    ]);

                    $created++;
                });
            }

            Notification::make()
                ->title("Carried forward $created balances")
                ->body("Skipped $skipped (no balance or already carried).")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::warning('FeeCarryForward failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Carry forward failed')->body($e->getMessage())->danger()->send();
        }
    }
}
