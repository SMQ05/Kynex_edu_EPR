<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\FeeReports;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Filament\Widgets\ChartWidget;

/**
 * Horizontal bar chart of outstanding amount grouped by class for the
 * current academic year. Helps the institute head see which class has
 * the most defaulters at a glance.
 */
class ClassWiseOutstandingWidget extends ChartWidget
{
    protected ?string $heading = 'Outstanding by class';

    protected static ?int $sort = 13;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getData(): array
    {
        $yearId = AcademicYear::query()->where('is_current', true)->value('id');

        // Per class: sum of (amount + fine - paid - discount) for unpaid/partial.
        $rows = StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('student_fees.academic_year_id', $yearId))
            ->whereIn('student_fees.status', ['pending', 'partial'])
            ->join('students', 'students.id', '=', 'student_fees.student_id')
            ->selectRaw('students.class_id, sum(student_fees.amount_paisas + student_fees.fine_paisas - student_fees.paid_paisas - student_fees.discount_paisas) as total')
            ->groupBy('students.class_id')
            ->pluck('total', 'class_id')
            ->all();

        if (empty($rows)) {
            return [
                'datasets' => [[
                    'label' => 'Outstanding (PKR)',
                    'data' => [0],
                    'backgroundColor' => '#dc2626',
                ]],
                'labels' => ['No data'],
            ];
        }

        $classNames = SchoolClass::query()
            ->whereIn('id', array_keys($rows))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $labels = [];
        $values = [];
        foreach ($classNames as $id => $name) {
            $labels[] = $name;
            $values[] = (int) round(((int) ($rows[$id] ?? 0)) / 100);
        }

        return [
            'datasets' => [[
                'label' => 'Outstanding (PKR)',
                'data' => $values,
                'backgroundColor' => 'rgba(220,38,38,0.7)',
                'borderColor' => '#dc2626',
                'borderWidth' => 1,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'PKR ' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
