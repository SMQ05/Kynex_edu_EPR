<?php

namespace App\Filament\SchoolAdmin\Widgets;

use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\OnlineClass;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Guard: tenant models are only available when tenancy is initialized
        if (! tenancy()->initialized) {
            return [
                Stat::make('Total Students', 0)
                    ->description('No tenant initialized')
                    ->color('gray'),
                Stat::make('Total Staff', 0)
                    ->description('No tenant initialized')
                    ->color('gray'),
                Stat::make('Pending Leave Requests', 0)
                    ->description('No tenant initialized')
                    ->color('gray'),
                Stat::make('Upcoming Classes', 0)
                    ->description('No tenant initialized')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('Total Students', Student::count())
                ->description('All enrolled students')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Total Staff', StaffProfile::count())
                ->description('Active staff members')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Pending Leave Requests', LeaveRequest::where('status', 'pending')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Upcoming Classes', OnlineClass::where('status', 'scheduled')->where('scheduled_at', '>=', now())->count())
                ->description('Online classes scheduled')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('primary'),
        ];
    }
}
