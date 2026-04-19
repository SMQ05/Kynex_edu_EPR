<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

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

class FeeReportsPage extends Page implements HasTable
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_financial_reports_limited';

    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Fee Reports';

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.school-admin.pages.fee-reports';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentFee::query()
                    ->with(['student', 'feeType', 'academicYear'])
                    ->whereIn('status', ['paid', 'partial', 'refunded'])
                    ->latest('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(['student.first_name', 'student.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('feeType.name')
                    ->label('Fee Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('academicYear.label')
                    ->label('Academic Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_paisas')
                    ->label('Paid')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_paisas')
                    ->label('Balance')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'     => 'success',
                        'partial'  => 'warning',
                        'pending'  => 'danger',
                        'waived'   => 'gray',
                        'refunded' => 'info',
                        default    => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid'     => 'Paid',
                        'partial'  => 'Partial',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                // ── Request Refund action (same as FeeCollectionPage) ──
                Action::make('requestRefund')
                    ->label('Request Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(function (): bool {
                        $user = auth()->user();
                        return $user->hasRole(['ACCOUNTANT', 'SCHOOL_ADMIN']);
                    })
                    ->hidden(fn (StudentFee $record): bool => $record->paid_paisas <= 0)
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
                            requestedBy: auth()->id(),
                        );

                        $formatted = FeesService::formatPkr($refundPaisas);
                        Notification::make()
                            ->title('Refund Request Submitted')
                            ->body("Refund of {$formatted} submitted for Institute Owner approval.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
