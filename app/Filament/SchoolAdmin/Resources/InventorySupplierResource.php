<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\InventorySupplierResource\Pages;
use App\Models\Tenant\InventorySupplier;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class InventorySupplierResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_inventory';

    protected static ?string $model = InventorySupplier::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Supplier Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                TextInput::make('email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('contact_person')
                    ->maxLength(255),

                Textarea::make('address')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone'),
                TextColumn::make('email'),
                TextColumn::make('contact_person'),
                TextColumn::make('transactions_count')->counts('transactions')->label('Transactions'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventorySuppliers::route('/'),
            'create' => Pages\CreateInventorySupplier::route('/create'),
            'edit' => Pages\EditInventorySupplier::route('/{record}/edit'),
        ];
    }
}
