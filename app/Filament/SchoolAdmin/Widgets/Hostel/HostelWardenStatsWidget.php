<?php

namespace App\Filament\SchoolAdmin\Widgets\Hostel;

use App\Models\Tenant\HostelAllocation;
use App\Models\Tenant\HostelGatePass;
use App\Models\Tenant\HostelRoom;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HostelWardenStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $totalRooms = HostelRoom::count();
        $occupiedBeds = HostelAllocation::where('status', 'active')->count();
        $totalCapacity = HostelRoom::sum('capacity');
        $availableBeds = $totalCapacity - $occupiedBeds;
        $pendingPasses = HostelGatePass::where('status', 'pending')->count();

        return [
            Stat::make('Total Rooms', $totalRooms)
                ->description('All hostel rooms')
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'),

            Stat::make('Occupied Beds', $occupiedBeds)
                ->description('Active allocations')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Available Beds', max(0, $availableBeds))
                ->description('Vacant capacity')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Gate Passes', $pendingPasses)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-ticket')
                ->color($pendingPasses > 0 ? 'warning' : 'gray'),
        ];
    }
}
