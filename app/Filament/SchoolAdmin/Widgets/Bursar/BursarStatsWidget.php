<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\Bursar;

use App\Models\ApprovalRequest;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BursarStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->active_role === 'BURSAR';
    }

    protected function getStats(): array
    {
        $collectedToday = FeePayment::whereDate('payment_date', today())
            ->sum('total_amount_paisas');

        $outstandingFees = StudentFee::whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(amount_paisas + fine_paisas - discount_paisas - paid_paisas) as total')
            ->value('total') ?? 0;

        $refundsPending = ApprovalRequest::pending()
            ->where('action_type', 'fee_refund')
            ->when(tenancy()->initialized, fn ($q) => $q->forTenant(tenant()->id))
            ->count();

        $overdueAccounts = StudentFee::where('status', 'pending')
            ->where('due_date', '<', today())
            ->distinct('student_id')
            ->count('student_id');

        return [
            Stat::make('Collected Today', 'PKR ' . number_format($collectedToday / 100, 2))
                ->description('Fee payments today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Outstanding Fees', 'PKR ' . number_format($outstandingFees / 100, 2))
                ->description('Total unpaid balance')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Refund Requests', $refundsPending)
                ->description('Pending approval')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($refundsPending > 0 ? 'warning' : 'gray'),

            Stat::make('Overdue Accounts', $overdueAccounts)
                ->description('Students past due')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueAccounts > 0 ? 'danger' : 'success'),
        ];
    }
}
