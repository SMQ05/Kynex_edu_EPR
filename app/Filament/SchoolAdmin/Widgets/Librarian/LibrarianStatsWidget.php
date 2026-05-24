<?php

namespace App\Filament\SchoolAdmin\Widgets\Librarian;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LibrarianStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        // Library module details will be built in Phase 5
        // These are stub stats that will be connected once library tables exist
        return [
            Stat::make('Total Books', 0)
                ->description('In catalogue')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Issued Today', 0)
                ->description('Books issued today')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('success'),

            Stat::make('Overdue Books', 0)
                ->description('Past return date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Fines Collected', 'PKR 0.00')
                ->description('This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
