<?php

namespace App\Filament\SchoolAdmin\Widgets\StudentRole;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        // Student role - stats are placeholders until attendance/homework/exam modules are fully built
        return [
            Stat::make('Attendance %', '—')
                ->description('This month')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Homework', 0)
                ->description('Assignments due')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Upcoming Exams', 0)
                ->description('Scheduled exams')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Library Books', 0)
                ->description('Currently issued')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),
        ];
    }
}
