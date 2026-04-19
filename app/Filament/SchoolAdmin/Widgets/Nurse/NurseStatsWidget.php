<?php

namespace App\Filament\SchoolAdmin\Widgets\Nurse;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NurseStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Health module detail = Phase 5
        // Stub stats for dashboard
        return [
            Stat::make('Medical Notes', 0)
                ->description('Students with records')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Clinic Visits Today', 0)
                ->description('Today')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('On Medication', 0)
                ->description('Students currently')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('warning'),

            Stat::make('Health Alerts', 0)
                ->description('Allergies & conditions')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
