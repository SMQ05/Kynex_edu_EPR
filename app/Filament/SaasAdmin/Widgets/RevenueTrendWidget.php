<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Revenue Trend Chart — SaaS Admin Dashboard
 *
 * Displays monthly revenue (collected invoices) over the last 12 months
 * as a line chart with PKR values on the Y-axis.
 */
class RevenueTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue Trend (12 Months)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = collect();
        $revenue = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthLabel = $date->format('M Y');
            $months->push($monthLabel);

            $monthlyRevenue = Invoice::query()
                ->where('status', InvoiceStatus::Paid)
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('total_paisas');

            $revenue->push(round($monthlyRevenue / 100));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (PKR)',
                    'data' => $revenue->toArray(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'PKR ' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
