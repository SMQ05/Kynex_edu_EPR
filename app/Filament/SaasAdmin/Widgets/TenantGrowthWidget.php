<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Tenant Growth Chart — SaaS Admin Dashboard
 *
 * Shows cumulative tenant count and monthly new signups over the last 12 months.
 */
class TenantGrowthWidget extends ChartWidget
{
    protected ?string $heading = 'School Growth (12 Months)';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = collect();
        $newSignups = collect();
        $cumulative = collect();

        // Get running total before the 12-month window
        $windowStart = Carbon::now()->subMonths(11)->startOfMonth();
        $priorCount = Tenant::where('created_at', '<', $windowStart)->count();

        $runningTotal = $priorCount;

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->format('M Y'));

            $monthCount = Tenant::query()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $newSignups->push($monthCount);
            $runningTotal += $monthCount;
            $cumulative->push($runningTotal);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cumulative Schools',
                    'data' => $cumulative->toArray(),
                    'borderColor' => '#1e40af',
                    'backgroundColor' => 'rgba(30, 64, 175, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'New Schools',
                    'data' => $newSignups->toArray(),
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.6)',
                    'type' => 'bar',
                    'yAxisID' => 'y1',
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
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Total Schools',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'New / Month',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
