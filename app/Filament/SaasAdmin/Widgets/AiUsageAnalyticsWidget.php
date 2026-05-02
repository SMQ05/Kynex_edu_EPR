<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Models\AiUsageLog;
use App\Models\Tenant;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * AI Usage Analytics Widget — SaaS Admin Dashboard
 *
 * Displays AI API consumption (cost in PKR) per month across all tenants,
 * plus a breakdown by top 5 consumers.
 */
class AiUsageAnalyticsWidget extends ChartWidget
{
    protected ?string $heading = 'AI Usage Cost (Last 6 Months)';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $months = collect();
        $totalCosts = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->format('M Y'));

            $monthlyCost = AiUsageLog::query()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('estimated_cost_paisas');

            $totalCosts->push(round($monthlyCost / 100));
        }

        // Top 5 tenant AI spenders this month
        $topSpenders = AiUsageLog::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('tenant_id, SUM(estimated_cost_paisas) as total_cost')
            ->groupBy('tenant_id')
            ->orderByDesc('total_cost')
            ->limit(5)
            ->get();

        $spenderLabels = $topSpenders->map(function ($log) {
            $tenant = Tenant::find($log->tenant_id);
            return $tenant?->school_name ?? 'Unknown';
        });

        $spenderValues = $topSpenders->map(fn ($log) => round($log->total_cost / 100));

        return [
            'datasets' => [
                [
                    'label' => 'AI Cost (PKR)',
                    'data' => $totalCosts->toArray(),
                    'borderColor' => '#7c3aed',
                    'backgroundColor' => [
                        'rgba(124, 58, 237, 0.2)',
                        'rgba(124, 58, 237, 0.3)',
                        'rgba(124, 58, 237, 0.4)',
                        'rgba(124, 58, 237, 0.5)',
                        'rgba(124, 58, 237, 0.6)',
                        'rgba(124, 58, 237, 0.7)',
                    ],
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
