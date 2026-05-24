<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\BankAccountResource\Pages;
use App\Models\Tenant\BankAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_accounts';

    protected static ?string $model = BankAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Bank Accounts';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Account')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('bank_name')->maxLength(255),
                    TextInput::make('account_number')->maxLength(255),
                    TextInput::make('branch')->maxLength(255),
                    TextInput::make('opening_balance_paisas')
                        ->label('Opening balance (PKR)')
                        ->numeric()->default(0)
                        ->helperText('Entered in rupees; stored as paisas.')
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('bank_name')->placeholder('—')->toggleable(),
                TextColumn::make('account_number')->placeholder('—')->toggleable(),
                TextColumn::make('current_balance_paisas')
                    ->label('Balance')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit'   => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
