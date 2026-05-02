<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();

        $totalThisMonth = Invoice::where('period_start', '>=', $currentMonth)
            ->sum('total_paisas');

        $paidThisMonth = Invoice::where('period_start', '>=', $currentMonth)
            ->where('status', InvoiceStatus::Paid)
            ->sum('total_paisas');

        $overdueTotal = Invoice::where('status', InvoiceStatus::Overdue)
            ->sum('total_paisas');

        $overdueCount = Invoice::where('status', InvoiceStatus::Overdue)->count();

        $unpaidCount = Invoice::whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Sent])
            ->count();

        $collectionRate = $totalThisMonth > 0
            ? round(($paidThisMonth / $totalThisMonth) * 100, 1)
            : 0;

        return [
            Stat::make('Billed This Month', 'PKR ' . number_format($totalThisMonth / 100, 0))
                ->description('Total invoiced')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Collected This Month', 'PKR ' . number_format($paidThisMonth / 100, 0))
                ->description("{$collectionRate}% collection rate")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Overdue Amount', 'PKR ' . number_format($overdueTotal / 100, 0))
                ->description("{$overdueCount} overdue invoices")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'gray'),

            Stat::make('Pending Invoices', (string) $unpaidCount)
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unpaidCount > 0 ? 'warning' : 'gray'),
        ];
    }
}
