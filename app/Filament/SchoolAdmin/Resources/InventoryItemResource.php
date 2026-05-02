<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\InventoryItemResource\Pages;
use App\Models\Tenant\InventoryItem;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class InventoryItemResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_inventory';

    protected static ?string $model = InventoryItem::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Items';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Item Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('unit')
                    ->default('piece')
                    ->maxLength(50),

                TextInput::make('minimum_quantity')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                TextInput::make('unit_price_pkr')
                    ->label('Unit Price (PKR)')
                    ->numeric()
                    ->prefix('PKR')
                    ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                    ->formatStateUsing(fn ($state, $record) => $record ? $record->unit_price_paisas / 100 : 0),

                Textarea::make('description')
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
                TextColumn::make('code')->searchable(),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('store.name')->label('Store'),
                TextColumn::make('current_quantity')
                    ->label('Qty')
                    ->sortable()
                    ->color(fn ($record) => $record->is_low_stock ? 'danger' : null)
                    ->weight(fn ($record) => $record->is_low_stock ? 'bold' : null),
                TextColumn::make('minimum_quantity')->label('Min Qty'),
                TextColumn::make('unit'),
                TextColumn::make('unit_price_paisas')
                    ->label('Unit Price')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 2)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('store_id')
                    ->relationship('store', 'name')
                    ->label('Store'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn ($query) => $query->lowStock()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
