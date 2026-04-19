<?php

namespace App\Filament\SchoolAdmin\Widgets\Teacher;

use App\Models\Tenant\ClassRoutine;
use App\Models\Tenant\ClassSubject;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();

        $myClassesCount = ClassSubject::where('teacher_id', $userId)->count();
        $todayPeriods = ClassRoutine::where('teacher_id', $userId)
            ->where('day_of_week', strtolower(now()->format('l')))
            ->count();

        return [
            Stat::make('My Classes', $myClassesCount)
                ->description('Subjects assigned')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make("Today's Periods", $todayPeriods)
                ->description(now()->format('l'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Pending Reviews', 0)
                ->description('Homework submissions')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),

            Stat::make('Online Classes', 0)
                ->description('Upcoming scheduled')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('success'),
        ];
    }
}
