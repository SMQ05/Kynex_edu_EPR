<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Enums\StudentFeeStatus;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Filament\Widgets\ChartWidget;

/**
 * Fee Status Distribution — School Admin Analytics
 *
 * Doughnut chart showing fee payment status breakdown.
 */
class FeeStatusDistributionWidget extends ChartWidget
{
    protected ?string $heading = 'Fee Status Distribution';

    protected static ?int $sort = 14;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getData(): array
    {
        $statusCounts = [];
        $labels = [];
        $bgColors = [];

        $colorMap = [
            'pending' => '#dc2626',
            'partial' => '#d97706',
            'paid' => '#16a34a',
            'waived' => '#0891b2',
            'refunded' => '#6b7280',
        ];

        foreach (StudentFeeStatus::cases() as $status) {
            $count = StudentFee::where('status', $status)->count();
            if ($count > 0) {
                $statusCounts[] = $count;
                $labels[] = ucfirst($status->value) . " ($count)";
                $bgColors[] = $colorMap[$status->value] ?? '#94a3b8';
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $statusCounts,
                    'backgroundColor' => $bgColors,
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 12,
                    ],
                ],
            ],
        ];
    }
}
