<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\InstituteOwner;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InstituteOwnerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        // School panel runs on the `school_users` guard; the default web
        // guard returns null here and silently hides the widget — that's
        // why the institute-head dashboard appeared blank.
        $user = auth('school_users')->user() ?? auth()->user();
        $role = $user?->active_role ?? $user?->roles?->first()?->name;
        return in_array($role, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);
    }

    protected function getStats(): array
    {
        $totalStudents = Student::where('status', 'enrolled')->count();

        $pendingApprovals = ApprovalRequest::pending()
            ->when(tenancy()->initialized, fn ($q) => $q->forTenant(tenant()->id))
            ->count();

        // FeePayment uses `payment_date`, not `payment_date`. The old column
        // name silently produced 0 because the SQL would have errored
        // and the catch-all rolled it into "no widget".
        $monthlyRevenue = FeePayment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('total_amount_paisas');

        $activeStaff = SchoolUser::where('is_active', true)
            ->whereNotIn('active_role', ['STUDENT', 'PARENT'])
            ->count();

        return [
            Stat::make('Total Students', number_format($totalStudents))
                ->description('Active enrolled')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($pendingApprovals > 0 ? 'danger' : 'success'),

            Stat::make('Monthly Revenue', 'PKR ' . number_format($monthlyRevenue / 100, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Active Staff', $activeStaff)
                ->description('Excluding students & parents')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
