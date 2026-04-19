<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\HrManager;

use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\StaffPayroll;
use App\Models\Tenant\StaffProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrManagerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->active_role === 'HR_MANAGER';
    }

    protected function getStats(): array
    {
        $totalStaff = StaffProfile::count();

        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->count();

        $payrollThisMonth = StaffPayroll::whereMonth('pay_month', now()->month)
            ->whereYear('pay_month', now()->year);
        $payrollGenerated = $payrollThisMonth->exists();
        $payrollAmount = $payrollThisMonth->sum('net_salary_paisas');

        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();

        return [
            Stat::make('Total Staff', $totalStaff)
                ->description('Active profiles')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('On Leave Today', $onLeaveToday)
                ->description('Approved leaves')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($onLeaveToday > 0 ? 'warning' : 'success'),

            Stat::make('Payroll This Month', $payrollGenerated
                ? 'PKR ' . number_format($payrollAmount / 100, 2)
                : 'Not Generated')
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($payrollGenerated ? 'success' : 'gray'),

            Stat::make('Pending Leave Requests', $pendingLeaves)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingLeaves > 0 ? 'warning' : 'success'),
        ];
    }
}
