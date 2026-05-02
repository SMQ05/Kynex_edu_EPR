<?php

namespace App\Filament\SchoolAdmin\Widgets\Cafeteria;

use App\Models\Tenant\InventoryItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CafeteriaLowStockWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Low Stock Inventory Items';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryItem::query()
                    ->whereColumn('current_quantity', '<=', 'minimum_quantity')
                    ->orderBy('current_quantity')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Item'),

                TextColumn::make('code')
                    ->label('Code'),

                TextColumn::make('current_quantity')
                    ->label('Current Stock')
                    ->color('danger'),

                TextColumn::make('minimum_quantity')
                    ->label('Min Required'),

                TextColumn::make('unit')
                    ->label('Unit'),

                TextColumn::make('store.name')
                    ->label('Store'),
            ])
            ->paginated(false);
    }
}
