<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\FeeReports;

use App\Models\Tenant\FeePayment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Last 12 months of fee collection (line chart) with a 3-month linear
 * projection appended. Projection uses ordinary least squares on the
 * last 6 actual data points — enough signal for tactical planning,
 * not enough to be misleading at scale.
 */
class CollectionTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Collection trend & 3-month projection';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getData(): array
    {
        $months = [];
        $actual = [];

        // Last 12 months actual collection.
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->startOfMonth()->subMonths($i);
            $months[] = $date->format('M Y');
            $actual[] = (int) round(((int) FeePayment::query()
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('total_amount_paisas')) / 100);
        }

        // Project the next 3 months by fitting a line over the last 6
        // actual points. Pad with leading nulls so Chart.js aligns the
        // dotted projection segment after the actuals.
        [$slope, $intercept] = $this->fitLine(array_slice($actual, -6));

        $projection = array_fill(0, count($actual), null);
        for ($i = 1; $i <= 3; $i++) {
            $months[] = Carbon::now()->startOfMonth()->addMonths($i)->format('M Y');
            $actual[] = null;
            $value = (int) round($intercept + $slope * (count($actual) - count($projection) + 5 + $i));
            $projection[] = max(0, $value);
        }

        // Glue the projection start point to the last actual so the
        // dotted line continues smoothly.
        $lastActual = end($actual);
        if (! is_int($lastActual)) {
            $idx = array_search(end($projection), $projection, true);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Collected (PKR)',
                    'data' => $actual,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22,163,74,0.10)',
                    'tension' => 0.25,
                    'fill' => true,
                    'spanGaps' => true,
                ],
                [
                    'label' => 'Projection (PKR)',
                    'data' => $projection,
                    'borderColor' => '#9ca3af',
                    'backgroundColor' => 'rgba(156,163,175,0.05)',
                    'borderDash' => [6, 4],
                    'tension' => 0.25,
                    'fill' => false,
                    'spanGaps' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    /**
     * Ordinary least squares on a numeric series. Returns [slope, intercept].
     *
     * @param  array<int|null>  $y
     * @return array{0:float,1:float}
     */
    private function fitLine(array $y): array
    {
        $clean = array_values(array_filter($y, fn ($v) => is_numeric($v)));
        $n = count($clean);
        if ($n < 2) {
            return [0.0, $clean[0] ?? 0.0];
        }

        $sumX = $sumY = $sumXY = $sumX2 = 0.0;
        foreach ($clean as $i => $val) {
            $x = (float) $i;
            $sumX += $x;
            $sumY += $val;
            $sumXY += $x * $val;
            $sumX2 += $x * $x;
        }

        $denominator = $n * $sumX2 - $sumX * $sumX;
        if ($denominator === 0.0) {
            return [0.0, $sumY / $n];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [$slope, $intercept];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true]],
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
