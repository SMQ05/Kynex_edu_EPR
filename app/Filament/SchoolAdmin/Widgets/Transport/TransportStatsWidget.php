<?php

namespace App\Filament\SchoolAdmin\Widgets\Transport;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransportStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Transport module details will be built in Phase 5
        // Stub stats for dashboard
        return [
            Stat::make('Total Vehicles', 0)
                ->description('In fleet')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Active Routes', 0)
                ->description('Operating routes')
                ->descriptionIcon('heroicon-m-map')
                ->color('success'),

            Stat::make('Students Using Transport', 0)
                ->description('Enrolled in transport')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Maintenance Alerts', 0)
                ->description('Vehicles needing service')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
        ];
    }
}
