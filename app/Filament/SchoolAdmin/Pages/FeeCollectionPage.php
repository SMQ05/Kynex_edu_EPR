<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use App\Services\FeesService;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class FeeCollectionPage extends Page implements HasTable
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'record_fee_payment';

    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Fee Collection';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees & Finance';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.school-admin.pages.fee-collection';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentFee::query()
                    ->with(['student', 'feeType', 'academicYear'])
                    ->latest('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(['student.first_name', 'student.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('feeType.name')
                    ->label('Fee Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_paisas')
                    ->label('Paid')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'pending' => 'danger',
                        'waived'  => 'gray',
                        'refunded' => 'info',
                        default   => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'partial'  => 'Partial',
                        'paid'     => 'Paid',
                        'waived'   => 'Waived',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                // ── Request Refund action ──
                Action::make('requestRefund')
                    ->label('Request Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(function (): bool {
                        $user = auth()->user();
                        return $user->hasRole(['ACCOUNTANT', 'SCHOOL_ADMIN']);
                    })
                    ->form([
                        Components\TextInput::make('refund_amount')
                            ->label('Refund Amount (PKR)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('PKR')
                            ->helperText(fn (StudentFee $record): string =>
                                'Max refundable: PKR ' . number_format($record->paid_paisas / 100, 2)
                            ),

                        Components\Textarea::make('reason')
                            ->label('Reason for Refund')
                            ->required()
                            ->minLength(20)
                            ->rows(3)
                            ->helperText('Minimum 20 characters required.'),
                    ])
                    ->action(function (StudentFee $record, array $data) {
                        $refundPaisas = (int) ($data['refund_amount'] * 100);

                        // Validate refund amount doesn't exceed paid amount
                        if ($refundPaisas > $record->paid_paisas) {
                            Notification::make()
                                ->title('Invalid Refund Amount')
                                ->body('Refund amount cannot exceed the paid amount.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $feesService = app(FeesService::class);
                        $feesService->initiateRefund(
                            studentFeeId: $record->id,
                            refundPaisas: $refundPaisas,
                            reason: $data['reason'],
                            requestedBy: auth()->guard('school_users')->id(),
                        );

                        $formatted = FeesService::formatPkr($refundPaisas);
                        Notification::make()
                            ->title('Refund Request Submitted')
                            ->body("Refund of {$formatted} submitted for Institute Owner approval.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('due_date', 'desc');
    }
}
