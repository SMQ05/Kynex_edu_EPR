<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Fee Collection Trend — School Admin Analytics
 *
 * Bar chart showing monthly fee collection vs outstanding for the current academic year.
 */
class FeeCollectionTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Fee Collection (Monthly)';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $months = collect();
        $collected = collect();
        $outstanding = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->format('M Y'));

            // Fee payments collected this month
            $monthlyCollected = FeePayment::query()
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('total_amount_paisas');

            $collected->push(round($monthlyCollected / 100));

            // Fees with due_date this month that are not fully paid
            $monthlyOutstanding = StudentFee::query()
                ->whereYear('due_date', $date->year)
                ->whereMonth('due_date', $date->month)
                ->whereIn('status', ['pending', 'partial'])
                ->get()
                ->sum(function ($fee) {
                    return $fee->balance_paisas;
                });

            $outstanding->push(round($monthlyOutstanding / 100));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Collected (PKR)',
                    'data' => $collected->toArray(),
                    'backgroundColor' => 'rgba(22, 163, 74, 0.7)',
                    'borderColor' => '#16a34a',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Outstanding (PKR)',
                    'data' => $outstanding->toArray(),
                    'backgroundColor' => 'rgba(220, 38, 38, 0.7)',
                    'borderColor' => '#dc2626',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => false,
                    'ticks' => [
                        'callback' => "function(value) { return 'PKR ' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
