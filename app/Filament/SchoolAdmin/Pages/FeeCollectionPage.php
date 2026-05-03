<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Jobs\SendFeeReminderWithFallback;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use App\Services\FeesService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Fee Collection — operator-facing UI to collect payments against
 * generated invoices, print receipts, send reminders, and request
 * refunds. Each row is a single StudentFee invoice; the operator can
 * collect a partial or full payment, print a paid receipt, or send a
 * defaulter reminder. Bulk operations let the bursar process a class
 * line at a time.
 */
class FeeCollectionPage extends Page implements HasTable
{
    use HasPermissionCheck;
    use InteractsWithTable;

    protected static string $rbacPermission = 'record_fee_payment';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Fee Collection';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.school-admin.pages.fee-collection';

    public function getSubheading(): ?string
    {
        return 'Each row is a single invoice. Click "Collect" to record a payment against it. Use "Receipt" to reprint any paid invoice.';
    }

    /**
     * Top KPI tiles: today's collection, this-month collection,
     * outstanding total, defaulter count.
     *
     * @return array<string,mixed>
     */
    public function summary(): array
    {
        $todayPaisas = (int) FeePayment::query()
            ->whereDate('payment_date', now()->toDateString())
            ->sum('total_amount_paisas');

        $monthPaisas = (int) FeePayment::query()
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('total_amount_paisas');

        $base = StudentFee::query()->whereIn('status', ['pending', 'partial']);
        $outstandingPaisas = (int) (clone $base)
            ->selectRaw('coalesce(sum(amount_paisas + fine_paisas - paid_paisas - discount_paisas), 0) as t')
            ->value('t');
        $defaulterCount = (int) (clone $base)
            ->where('due_date', '<', now()->toDateString())
            ->distinct('student_id')
            ->count('student_id');

        return [
            'today'        => 'PKR ' . number_format($todayPaisas / 100, 2),
            'month'        => 'PKR ' . number_format($monthPaisas / 100, 2),
            'outstanding'  => 'PKR ' . number_format($outstandingPaisas / 100, 2),
            'defaulters'   => $defaulterCount,
            'today_count'  => (int) FeePayment::query()->whereDate('payment_date', now()->toDateString())->count(),
            'month_count'  => (int) FeePayment::query()
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Keep the query clean — Filament handles sort + pagination
                // re-renders gracefully when there's no manual orderByRaw
                // intermixed with user-driven sorts.
                StudentFee::query()->with(['student.schoolClass', 'student.section', 'feeType', 'academicYear'])
            )
            ->defaultSort('due_date', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('student', function ($q) use ($search) {
                            $q->where(function ($q) use ($search) {
                                $q->where('first_name', 'ilike', "%$search%")
                                  ->orWhere('last_name', 'ilike', "%$search%")
                                  ->orWhere('admission_number', 'ilike', "%$search%")
                                  ->orWhere('registration_number', 'ilike', "%$search%");
                            });
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('feeType.name')
                    ->label('Fee Type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Bill Month')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_paisas')
                    ->label('Billed')
                    ->money('PKR', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('fine_paisas')
                    ->label('Fine')
                    ->money('PKR', divideBy: 100)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discount_paisas')
                    ->label('Discount')
                    ->money('PKR', divideBy: 100)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('paid_paisas')
                    ->label('Paid')
                    ->money('PKR', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst(
                        $state instanceof \BackedEnum ? $state->value : (string) $state,
                    ))
                    ->color(function ($state): string {
                        $v = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        return match ($v) {
                            'paid'     => 'success',
                            'partial'  => 'warning',
                            'pending'  => 'danger',
                            'waived'   => 'gray',
                            'refunded' => 'info',
                            default    => 'gray',
                        };
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending'  => 'Pending',
                    'partial'  => 'Partial',
                    'paid'     => 'Paid',
                    'waived'   => 'Waived',
                    'refunded' => 'Refunded',
                ]),
                SelectFilter::make('class_id')
                    ->label('Class')
                    ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('student', fn ($q) => $q->where('class_id', $data['value']))
                        : $query),
                SelectFilter::make('academic_year_id')
                    ->label('Academic Year')
                    ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')),
                TernaryFilter::make('is_overdue')
                    ->label('Overdue?')
                    ->placeholder('All invoices')
                    ->trueLabel('Overdue only')
                    ->falseLabel('Not overdue')
                    ->queries(
                        true:  fn ($q) => $q->whereIn('status', ['pending', 'partial'])->where('due_date', '<', now()->toDateString()),
                        false: fn ($q) => $q->where(function ($q) {
                            $q->whereNotIn('status', ['pending', 'partial'])
                              ->orWhere('due_date', '>=', now()->toDateString());
                        }),
                        blank: fn ($q) => $q,
                    ),
            ])
            ->actions([
                $this->collectAction(),
                $this->markPaidAction(),
                $this->printReceiptAction(),
                $this->sendReminderAction(),
                $this->discountAction(),
                $this->waiveAction(),
                $this->refundAction(),
            ])
            ->bulkActions([
                BulkAction::make('bulkRemind')
                    ->label('Send reminders')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $queued = 0;
                        foreach ($records as $r) {
                            if (! in_array($r->status_value(), ['pending', 'partial'], true)) continue;
                            try {
                                SendFeeReminderWithFallback::dispatch(
                                    studentFeeId: $r->id,
                                    primaryChannel: 'whatsapp',
                                    isFallback: false,
                                );
                                $queued++;
                            } catch (\Throwable) {}
                        }
                        Notification::make()->title("Queued $queued reminders")->success()->send();
                    }),
            ]);
    }

    // ── Row actions ──────────────────────────────────────────────────

    private function collectAction(): Action
    {
        return Action::make('collect')
            ->label('Collect')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (StudentFee $r) => in_array($r->status_value(), ['pending', 'partial'], true))
            ->modalHeading(fn (StudentFee $r) => 'Collect payment — ' . $r->student?->full_name)
            ->modalDescription(fn (StudentFee $r) => sprintf(
                'Net due: PKR %s · Fee type: %s',
                number_format(max(0, $r->amount_paisas + $r->fine_paisas - $r->paid_paisas - $r->discount_paisas) / 100, 2),
                $r->feeType?->name ?? '—',
            ))
            ->form(fn (StudentFee $r) => [
                Components\TextInput::make('amount_pkr')
                    ->label('Amount being paid (PKR)')
                    ->numeric()
                    ->minValue(1)
                    ->step('0.01')
                    ->prefix('PKR')
                    ->required()
                    ->default(fn () => round(max(0, $r->amount_paisas + $r->fine_paisas - $r->paid_paisas - $r->discount_paisas) / 100, 2))
                    ->helperText('You can take a partial payment — the balance will remain due.'),

                Components\Select::make('payment_method')
                    ->label('Method')
                    ->options([
                        'cash'         => 'Cash',
                        'bank'         => 'Bank Transfer',
                        'cheque'       => 'Cheque',
                        'card'         => 'Card',
                        'easypaisa'    => 'EasyPaisa',
                        'jazzcash'     => 'JazzCash',
                        'online'       => 'Online (other)',
                    ])
                    ->default('cash')
                    ->required(),

                Components\TextInput::make('bank_reference')
                    ->label('Reference / Cheque No / Transaction ID')
                    ->maxLength(100)
                    ->nullable(),

                Components\Textarea::make('notes')
                    ->label('Note (optional)')
                    ->rows(2)
                    ->maxLength(500)
                    ->nullable(),
            ])
            ->action(function (StudentFee $record, array $data) {
                $rupees = (float) $data['amount_pkr'];
                $paisas = (int) round($rupees * 100);
                $netDue = max(0, $record->amount_paisas + $record->fine_paisas - $record->paid_paisas - $record->discount_paisas);

                if ($paisas > $netDue) {
                    Notification::make()
                        ->title('Amount exceeds net due')
                        ->body('Cannot collect more than the outstanding balance. Use a refund to return excess.')
                        ->danger()->send();
                    return;
                }

                try {
                    $payment = app(FeesService::class)->collectPayment(
                        studentId: $record->student_id,
                        feeAllocations: [$record->id => $paisas],
                        paymentMethod: $data['payment_method'] ?? 'cash',
                        collectedBy: auth()->guard('school_users')->id() ?? '00000000000000000000000000',
                        transactionRef: $data['bank_reference'] ?? null,
                        remarks: $data['notes'] ?? null,
                    );

                    Notification::make()
                        ->title('Payment recorded · Receipt ' . $payment->receipt_number)
                        ->body('PKR ' . number_format($paisas / 100, 2) . ' collected. Click Receipt to print.')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('FeeCollection collect failed', [
                        'student_fee_id' => $record->id,
                        'error' => $e->getMessage(),
                    ]);
                    Notification::make()->title('Could not record payment')->body($e->getMessage())->danger()->send();
                }
            });
    }

    private function markPaidAction(): Action
    {
        return Action::make('markPaid')
            ->label('Mark paid in full')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (StudentFee $r) => in_array($r->status_value(), ['pending', 'partial'], true))
            ->modalDescription('Records a single cash payment for the entire net-due amount and prints the receipt.')
            ->action(function (StudentFee $record) {
                $netDue = max(0, $record->amount_paisas + $record->fine_paisas - $record->paid_paisas - $record->discount_paisas);
                if ($netDue <= 0) {
                    Notification::make()->title('Already paid')->warning()->send();
                    return;
                }

                try {
                    $payment = app(FeesService::class)->collectPayment(
                        studentId: $record->student_id,
                        feeAllocations: [$record->id => $netDue],
                        paymentMethod: 'cash',
                        collectedBy: auth()->guard('school_users')->id() ?? '00000000000000000000000000',
                    );
                    Notification::make()
                        ->title('Marked paid · Receipt ' . $payment->receipt_number)
                        ->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    private function printReceiptAction(): Action
    {
        return Action::make('receipt')
            ->label('Receipt')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(function (StudentFee $r): ?string {
                $payment = FeePayment::query()
                    ->whereHas('items', fn ($q) => $q->where('student_fee_id', $r->id))
                    ->orderByDesc('payment_date')
                    ->first();
                return $payment ? route('fee.receipt', ['payment' => $payment->id]) : null;
            })
            ->openUrlInNewTab()
            ->visible(fn (StudentFee $r) => $r->paid_paisas > 0);
    }

    private function sendReminderAction(): Action
    {
        return Action::make('remind')
            ->label('Remind')
            ->icon('heroicon-o-bell-alert')
            ->color('warning')
            ->visible(fn (StudentFee $r) => in_array($r->status_value(), ['pending', 'partial'], true))
            ->requiresConfirmation()
            ->modalDescription('Sends a WhatsApp reminder to the primary guardian (SMS fallback after 24h).')
            ->action(function (StudentFee $record) {
                try {
                    SendFeeReminderWithFallback::dispatch(
                        studentFeeId: $record->id,
                        primaryChannel: 'whatsapp',
                        isFallback: false,
                    );
                    Notification::make()->title('Reminder queued')->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    private function discountAction(): Action
    {
        return Action::make('discount')
            ->label('Discount')
            ->icon('heroicon-o-receipt-percent')
            ->color('info')
            ->visible(fn (StudentFee $r) => in_array($r->status_value(), ['pending', 'partial'], true))
            ->form(fn (StudentFee $r) => [
                Components\TextInput::make('discount_pkr')
                    ->label('Discount (PKR)')
                    ->numeric()->minValue(0)->step('0.01')->required()
                    ->default(round($r->discount_paisas / 100, 2))
                    ->helperText('Reduces the net due. Existing discount is replaced.'),
                Components\Textarea::make('reason')
                    ->required()->minLength(5)->rows(2),
            ])
            ->action(function (StudentFee $record, array $data) {
                $paisas = (int) round(((float) $data['discount_pkr']) * 100);
                $maxAllowed = (int) $record->amount_paisas + (int) $record->fine_paisas - (int) $record->paid_paisas;
                if ($paisas > $maxAllowed) {
                    Notification::make()->title('Discount too high')
                        ->body('Cannot exceed the unpaid portion (PKR ' . number_format($maxAllowed/100, 2) . ').')
                        ->danger()->send();
                    return;
                }
                try {
                    app(FeesService::class)->applyDiscount(
                        studentFeeId: $record->id,
                        discountPaisas: $paisas,
                        reason: $data['reason'],
                        appliedBy: auth()->guard('school_users')->id() ?? '00000000000000000000000000',
                    );
                    Notification::make()->title('Discount applied')->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    private function waiveAction(): Action
    {
        return Action::make('waive')
            ->label('Waive')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (StudentFee $r) => in_array($r->status_value(), ['pending', 'partial'], true))
            ->modalDescription('Waives the entire balance (e.g. scholarship, hardship). Cannot be undone via this UI.')
            ->form([
                Components\Textarea::make('reason')->required()->minLength(10)->rows(2),
            ])
            ->action(function (StudentFee $record, array $data) {
                $record->update([
                    'status' => 'waived',
                    'discount_paisas' => $record->amount_paisas + $record->fine_paisas - $record->paid_paisas,
                    'remarks' => 'Waived: ' . $data['reason'],
                ]);
                Notification::make()->title('Invoice waived')->success()->send();
            });
    }

    private function refundAction(): Action
    {
        return Action::make('requestRefund')
            ->label('Refund')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->visible(fn (StudentFee $r) => $r->paid_paisas > 0)
            ->form(fn (StudentFee $r) => [
                Components\TextInput::make('refund_pkr')
                    ->label('Refund Amount (PKR)')
                    ->numeric()->required()->minValue(1)->step('0.01')->prefix('PKR')
                    ->helperText('Max refundable: PKR ' . number_format($r->paid_paisas / 100, 2)),
                Components\Textarea::make('reason')->required()->minLength(20)->rows(3),
            ])
            ->action(function (StudentFee $record, array $data) {
                $refundPaisas = (int) round(((float) $data['refund_pkr']) * 100);
                if ($refundPaisas > $record->paid_paisas) {
                    Notification::make()->title('Refund exceeds paid')->danger()->send();
                    return;
                }
                try {
                    app(FeesService::class)->initiateRefund(
                        studentFeeId: $record->id,
                        refundPaisas: $refundPaisas,
                        reason: $data['reason'],
                        requestedBy: auth()->guard('school_users')->id(),
                    );
                    Notification::make()->title('Refund request submitted for approval')->warning()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
