<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Filament\Widgets\ChartWidget;

/**
 * Tenant Status Pie Chart — SaaS Admin Dashboard
 *
 * Shows the distribution of tenant statuses (Active, Trial, Suspended, Cancelled).
 */
class TenantStatusPieWidget extends ChartWidget
{
    protected ?string $heading = 'School Status Distribution';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $statuses = [
            TenantStatus::Active,
            TenantStatus::Trial,
            TenantStatus::Suspended,
            TenantStatus::Expired,
        ];

        $counts = [];
        $labels = [];
        $backgroundColors = [];

        $colorMap = [
            'active' => '#16a34a',
            'trial' => '#0891b2',
            'suspended' => '#dc2626',
            'expired' => '#6b7280',
        ];

        foreach ($statuses as $status) {
            $count = Tenant::where('status', $status)->count();
            if ($count > 0) {
                $counts[] = $count;
                $labels[] = $status->label() . " ($count)";
                $backgroundColors[] = $colorMap[$status->value] ?? '#94a3b8';
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $counts,
                    'backgroundColor' => $backgroundColors,
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
                ],
            ],
        ];
    }
}
