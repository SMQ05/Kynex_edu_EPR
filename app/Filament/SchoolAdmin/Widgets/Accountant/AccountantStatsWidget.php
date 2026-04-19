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

    protected function getStats(): array
    {
        $todayCollected = FeePayment::whereDate('paid_at', today())
            ->sum('amount_paid_paisas');

        $monthCollected = FeePayment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount_paid_paisas');

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
