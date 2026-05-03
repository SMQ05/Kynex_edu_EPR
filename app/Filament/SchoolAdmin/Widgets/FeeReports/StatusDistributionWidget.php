<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\FeeReports;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\StudentFee;
use Filament\Widgets\ChartWidget;

/**
 * Pie chart of student-fee status distribution for the current
 * academic year: paid / partial / pending / waived / refunded.
 */
class StatusDistributionWidget extends ChartWidget
{
    protected ?string $heading = 'Invoice status (current year)';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $yearId = AcademicYear::query()->where('is_current', true)->value('id');

        $rows = StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $order  = ['paid', 'partial', 'pending', 'waived', 'refunded'];
        $colors = [
            'paid'     => '#16a34a',
            'partial'  => '#f59e0b',
            'pending'  => '#dc2626',
            'waived'   => '#6b7280',
            'refunded' => '#3b82f6',
        ];

        $labels = [];
        $values = [];
        $bg = [];
        foreach ($order as $status) {
            if (! isset($rows[$status])) {
                continue;
            }
            $labels[] = ucfirst($status);
            $values[] = (int) $rows[$status];
            $bg[] = $colors[$status];
        }

        return [
            'datasets' => [[
                'label' => 'Invoices',
                'data' => $values,
                'backgroundColor' => $bg,
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
