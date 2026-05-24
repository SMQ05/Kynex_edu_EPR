<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\WalletResource\Pages;
use App\Filament\SchoolAdmin\Resources\WalletResource\RelationManagers\TransactionsRelationManager;
use App\Models\Tenant\Wallet;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_wallet';

    protected static ?string $model = Wallet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Wallets';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Wallet')->schema([
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->getStateUsing(fn (Wallet $r) => trim(($r->student->first_name ?? '') . ' ' . ($r->student->last_name ?? '')))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('student.admission_number')->label('Adm #')->searchable(),
                TextColumn::make('balance_paisas')
                    ->label('Balance')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('balance_paisas', 'desc')
            ->actions([
                Action::make('topUp')
                    ->label('Top up')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount (PKR)')->numeric()->required()->minValue(1),
                        TextInput::make('reference')->maxLength(255),
                        Textarea::make('note')->rows(2),
                    ])
                    ->action(function (Wallet $record, array $data): void {
                        $record->credit((int) round((float) $data['amount'] * 100), 'adjustment', $data['reference'] ?? null, $data['note'] ?? 'Admin top-up');
                        Notification::make()->title('Wallet topped up')->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [TransactionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'edit'  => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
