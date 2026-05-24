<?php

namespace App\Filament\SchoolAdmin\Widgets\ParentRole;

use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use App\Models\Tenant\StudentGuardian;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $userId = auth()->guard('school_users')->id();

        // Find children linked to this parent via guardians table
        $childrenIds = StudentGuardian::where('school_user_id', $userId)
            ->pluck('student_id');

        $childrenCount = $childrenIds->count();

        $totalDue = StudentFee::whereIn('student_id', $childrenIds)
            ->where('status', 'unpaid')
            ->sum('amount_paisas');

        return [
            Stat::make('My Children', $childrenCount)
                ->description('Enrolled students')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Fees Due', 'PKR ' . number_format($totalDue / 100, 2))
                ->description('Across all children')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalDue > 0 ? 'danger' : 'success'),

            Stat::make('Next Exam', 'Check Schedule')
                ->description('View exam timetable')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),
        ];
    }
}
