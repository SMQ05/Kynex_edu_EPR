<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\FeeReports;

use App\Models\Tenant\StudentFee;
use Filament\Widgets\ChartWidget;

/**
 * Aging analysis: count of overdue invoices bucketed by age in days.
 * Buckets: 1–30, 31–60, 61–90, 90+. Helps the bursar prioritise
 * collection calls (older = riskier).
 */
class DefaulterAgingWidget extends ChartWidget
{
    protected ?string $heading = 'Defaulter aging (days late)';

    protected static ?int $sort = 14;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $today = now()->toDateString();

        $rows = StudentFee::query()
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $today)
            ->selectRaw(<<<'SQL'
                COUNT(*) FILTER (WHERE NOW()::date - due_date BETWEEN 1 AND 30)   AS bucket_1_30,
                COUNT(*) FILTER (WHERE NOW()::date - due_date BETWEEN 31 AND 60)  AS bucket_31_60,
                COUNT(*) FILTER (WHERE NOW()::date - due_date BETWEEN 61 AND 90)  AS bucket_61_90,
                COUNT(*) FILTER (WHERE NOW()::date - due_date > 90)               AS bucket_90_plus
            SQL)
            ->first();

        return [
            'datasets' => [[
                'label' => 'Overdue invoices',
                'data' => [
                    (int) ($rows->bucket_1_30 ?? 0),
                    (int) ($rows->bucket_31_60 ?? 0),
                    (int) ($rows->bucket_61_90 ?? 0),
                    (int) ($rows->bucket_90_plus ?? 0),
                ],
                'backgroundColor' => ['#fbbf24', '#f97316', '#ef4444', '#7f1d1d'],
                'borderWidth' => 0,
            ]],
            'labels' => ['1–30 days', '31–60 days', '61–90 days', '90+ days'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
