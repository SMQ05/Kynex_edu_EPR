<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\WalletRefundResource\Pages;
use App\Models\Tenant\WalletRefund;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WalletRefundResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_wallet';

    protected static ?string $model = WalletRefund::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Wallet Refunds';

    protected static ?int $navigationSort = 12;

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::where('status', 'pending')->count();
        } catch (\Throwable) {
            return null; // table not migrated yet — never break the panel nav
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Refund')
                ->columns(2)
                ->schema([
                    Select::make('student_id')
                        ->label('Student')
                        ->relationship('student', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->first_name . ' ' . $record->last_name))
                        ->searchable(['first_name', 'last_name', 'admission_number'])
                        ->preload()->required(),
                    TextInput::make('amount_paisas')
                        ->label('Amount (PKR)')
                        ->numeric()->required()->minValue(1)
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                    Select::make('method')
                        ->options(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'online' => 'Online'])
                        ->native(false)->nullable(),
                    Textarea::make('reason')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->date('d M Y')->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->getStateUsing(fn (WalletRefund $r) => trim(($r->student->first_name ?? '') . ' ' . ($r->student->last_name ?? '')))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => WalletRefund::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(WalletRefund::STATUSES)->default('pending'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Approving will debit the student\'s wallet by this amount.')
                    ->visible(fn (WalletRefund $r): bool => $r->status === 'pending')
                    ->action(function (WalletRefund $record): void {
                        try {
                            $record->approve();
                            Notification::make()->title('Refund approved — wallet debited')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (WalletRefund $r): bool => $r->status === 'pending')
                    ->action(function (WalletRefund $record): void {
                        $record->reject();
                        Notification::make()->title('Refund rejected')->success()->send();
                    }),
                EditAction::make()->visible(fn (WalletRefund $r): bool => $r->status === 'pending'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWalletRefunds::route('/'),
            'create' => Pages\CreateWalletRefund::route('/create'),
            'edit'   => Pages\EditWalletRefund::route('/{record}/edit'),
        ];
    }
}
