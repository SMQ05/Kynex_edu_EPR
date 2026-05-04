<?php

namespace App\Filament\SchoolAdmin\Widgets\CampusOwner;

use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampusOwnerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $campusId = auth()->user()->campus_id;

        return [
            Stat::make('Enrolled Students', Student::where('campus_id', $campusId)->where('status', 'enrolled')->count())
                ->description('In your campus')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Staff Members', StaffProfile::where('campus_id', $campusId)->count())
                ->description('Active staff')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Fees Collected (Month)', 'PKR ' . number_format(
                FeePayment::whereHas('studentFee.student', fn ($q) => $q->where('campus_id', $campusId))
                    ->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum('total_amount_paisas') / 100, 2
            ))
                ->description('This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Campuses', 'Your Campus')
                ->description('Campus-level view')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
        ];
    }
}
