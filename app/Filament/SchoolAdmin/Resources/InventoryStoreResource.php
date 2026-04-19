<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\InventoryStoreResource\Pages;
use App\Models\Tenant\InventoryStore;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class InventoryStoreResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_inventory';

    protected static ?string $model = InventoryStore::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stores';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Store Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('location')
                    ->maxLength(255),

                Select::make('manager_id')
                    ->label('Store Manager')
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('campus_id')
                    ->relationship('campus', 'name')
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('location'),
                TextColumn::make('manager.name')->label('Manager'),
                TextColumn::make('campus.name')->label('Campus'),
                TextColumn::make('items_count')->counts('items')->label('Items'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryStores::route('/'),
            'create' => Pages\CreateInventoryStore::route('/create'),
            'edit' => Pages\EditInventoryStore::route('/{record}/edit'),
        ];
    }
}
