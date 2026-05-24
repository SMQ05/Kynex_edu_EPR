<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\AttendanceClerk;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceClerkStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $totalMarkedToday = AttendanceRecord::forDate($today)->count();

        $absentToday = AttendanceRecord::forDate($today)
            ->where('status', 'absent')
            ->count();

        $totalEnrolled = Student::enrolled()->count();

        $presentToday = AttendanceRecord::forDate($today)
            ->where('status', 'present')
            ->count();

        $attendanceRate = $totalMarkedToday > 0
            ? round(($presentToday / $totalMarkedToday) * 100, 1)
            : 0;

        return [
            Stat::make('Marked Today', $totalMarkedToday)
                ->description('Students marked')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Absent Today', $absentToday)
                ->description('Absent students')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Attendance Rate', $attendanceRate . '%')
                ->description("Today's rate")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($attendanceRate >= 80 ? 'success' : 'warning'),

            Stat::make('Total Enrolled', $totalEnrolled)
                ->description('Active students')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),
        ];
    }
}
