<?php

namespace App\Filament\SchoolAdmin\Widgets\Counselor;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CounselorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        // Behavior/counseling module detail = Phase 5
        // Stub stats for dashboard
        return [
            Stat::make('Active Cases', 0)
                ->description('Counseling cases')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('primary'),

            Stat::make('Behavior Incidents', 0)
                ->description('This month')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('Pending Follow-ups', 0)
                ->description('Scheduled sessions')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Resolved Cases', 0)
                ->description('This year')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
