<?php

namespace App\Filament\SchoolAdmin\Widgets\Accountant;

use App\Models\Tenant\Expense;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountantStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $todayCollected = FeePayment::whereDate('payment_date', today())
            ->sum('total_amount_paisas');

        $monthCollected = FeePayment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('total_amount_paisas');

        $totalDue = StudentFee::where('status', 'unpaid')
            ->sum('amount_paisas');

        return [
            Stat::make('Collected Today', 'PKR ' . number_format($todayCollected / 100, 2))
                ->description('Fee payments today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Month Collection', 'PKR ' . number_format($monthCollected / 100, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Total Due', 'PKR ' . number_format($totalDue / 100, 2))
                ->description('Outstanding fees')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Recent Expenses', Expense::whereMonth('expense_date', now()->month)->count())
                ->description('This month')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),
        ];
    }
}
