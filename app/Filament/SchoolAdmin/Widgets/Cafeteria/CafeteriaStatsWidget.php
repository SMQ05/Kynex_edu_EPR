<?php

namespace App\Filament\SchoolAdmin\Widgets\Cafeteria;

use App\Models\Tenant\InventoryItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CafeteriaStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        // Cafeteria POS detail = Phase 5
        // Low stock items from inventory (cafeteria store)
        $lowStockCount = InventoryItem::whereColumn('current_quantity', '<=', 'minimum_quantity')
            ->where('current_quantity', '>', 0)
            ->count();

        return [
            Stat::make('Wallet Transactions', 0)
                ->description('Today')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),

            Stat::make('Menu Items', 0)
                ->description('Active items')
                ->descriptionIcon('heroicon-m-cake')
                ->color('success'),

            Stat::make('Low Stock Items', $lowStockCount)
                ->description('Need restocking')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Daily Revenue', 'PKR 0.00')
                ->description('Today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
