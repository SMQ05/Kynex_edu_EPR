<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Enums\LeaveStatus;
use App\Enums\StaffAttendanceStatus;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\StaffAttendanceRecord;
use App\Models\Tenant\StaffProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = today();

        $totalStaff = StaffProfile::count();

        $onLeaveToday = LeaveRequest::where('status', LeaveStatus::Approved->value)
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->count();

        $pendingLeaves = LeaveRequest::where('status', LeaveStatus::Pending->value)->count();

        $presentToday = StaffAttendanceRecord::whereDate('date', $today)
            ->where('status', StaffAttendanceStatus::Present->value)
            ->count();

        $absentToday = StaffAttendanceRecord::whereDate('date', $today)
            ->where('status', StaffAttendanceStatus::Absent->value)
            ->count();

        return [
            Stat::make('Total Staff', $totalStaff)
                ->description('Active staff members')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Present Today', $presentToday . ' / ' . $totalStaff)
                ->description($absentToday > 0 ? "{$absentToday} absent" : 'All marked present')
                ->icon('heroicon-o-check-badge')
                ->color($absentToday > 0 ? 'warning' : 'success'),

            Stat::make('On Leave Today', $onLeaveToday)
                ->description('Approved leave')
                ->icon('heroicon-o-calendar-days')
                ->color($onLeaveToday > 0 ? 'info' : 'gray'),

            Stat::make('Pending Leave Requests', $pendingLeaves)
                ->description($pendingLeaves > 0 ? 'Needs review' : 'All clear')
                ->icon('heroicon-o-clock')
                ->color($pendingLeaves > 0 ? 'warning' : 'success'),
        ];
    }
}
