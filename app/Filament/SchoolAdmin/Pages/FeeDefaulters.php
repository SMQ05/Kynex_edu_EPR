<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Jobs\SendFeeReminderWithFallback;
use App\Models\Tenant\StudentFee;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Lists fee invoices that are past their due_date and not fully paid.
 * Bulk action dispatches the existing SendFeeReminderWithFallback job
 * for each selected row, so the reminder pipeline (WhatsApp → SMS
 * fallback at 24h) is reused for the manual nudge.
 */
class FeeDefaulters extends Page implements HasTable
{
    use HasPermissionCheck;
    use InteractsWithTable;

    protected static string $rbacPermission = 'view_fee_payments';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Defaulters';

    protected string $view = 'filament.school-admin.pages.fee-defaulters';

    public function getTitle(): string
    {
        return 'Fee Defaulters';
    }

    public function getSubheading(): ?string
    {
        return 'Students with one or more overdue invoices. Select rows and click "Send reminder" to dispatch a WhatsApp message to each guardian (with SMS fallback after 24h).';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentFee::query()
                    ->with(['student.schoolClass', 'student.section', 'student.guardians', 'feeType', 'academicYear'])
                    ->whereIn('status', ['pending', 'partial'])
                    ->where('due_date', '<', now()->toDateString())
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(['student.first_name', 'student.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.schoolClass.name')
                    ->label('Class')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('student.section.name')
                    ->label('Section')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('feeType.name')
                    ->label('Fee Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => 'danger'),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days late')
                    ->getStateUsing(fn (StudentFee $r) => $r->due_date
                        ? (int) \Carbon\Carbon::parse($r->due_date)->diffInDays(now())
                        : 0)
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->getStateUsing(fn (StudentFee $r) => 'PKR ' . number_format(
                        max(0, ((int) $r->amount_paisas) + ((int) $r->fine_paisas) - ((int) $r->paid_paisas) - ((int) $r->discount_paisas)) / 100,
                        2,
                    )),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'partial' => 'Partial',
                ]),
            ])
            ->bulkActions([
                BulkAction::make('sendReminder')
                    ->label('Send reminder')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send fee reminders')
                    ->modalDescription('A reminder will be queued for each selected invoice. Primary channel is WhatsApp, with SMS fallback after 24h if the WhatsApp send fails.')
                    ->form([
                        Select::make('channel')
                            ->label('Primary channel')
                            ->options([
                                'whatsapp' => 'WhatsApp (recommended)',
                                'sms'      => 'SMS',
                                'email'    => 'Email',
                            ])
                            ->default('whatsapp')
                            ->required(),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        $channel = $data['channel'] ?? 'whatsapp';
                        $queued = 0;
                        foreach ($records as $record) {
                            try {
                                SendFeeReminderWithFallback::dispatch(
                                    studentFeeId: $record->id,
                                    primaryChannel: $channel,
                                    isFallback: false,
                                );
                                $queued++;
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning('FeeDefaulters reminder dispatch failed', [
                                    'student_fee_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                        Notification::make()
                            ->title("Queued {$queued} reminders ({$channel})")
                            ->body('Each one will retry up to 3 times. SMS fallback fires automatically 24h after a primary failure.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function summary(): array
    {
        $base = StudentFee::query()
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString());

        $count = (clone $base)->count();
        $studentsCount = (clone $base)->distinct('student_id')->count('student_id');
        $totalDuePaisas = (clone $base)
            ->selectRaw('coalesce(sum(amount_paisas + fine_paisas - paid_paisas - discount_paisas), 0) as t')
            ->value('t');

        return [
            'invoices_overdue' => $count,
            'students_overdue' => $studentsCount,
            'total_due' => 'PKR ' . number_format(((int) $totalDuePaisas) / 100, 2),
        ];
    }
}
