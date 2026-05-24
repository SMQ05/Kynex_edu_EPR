<?php

namespace App\Filament\SchoolAdmin\Widgets\Receptionist;

use App\Models\Tenant\Visitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReceptionistStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $currentlyInside = Visitor::where('status', 'checked_in')->count();
        $todayTotal = Visitor::whereDate('check_in_time', today())->count();

        return [
            Stat::make('Currently Inside', $currentlyInside)
                ->description('Visitors on premises')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($currentlyInside > 0 ? 'primary' : 'gray'),

            Stat::make('Total Today', $todayTotal)
                ->description('Visitors today')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Inquiries This Week', 0)
                ->description('Admission inquiries')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('warning'),
        ];
    }
}
