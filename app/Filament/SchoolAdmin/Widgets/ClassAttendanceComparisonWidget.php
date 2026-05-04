<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\SchoolClass;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Class-wise Attendance Comparison — School Admin Analytics
 *
 * Radar chart showing attendance rate per class for the current month.
 */
class ClassAttendanceComparisonWidget extends ChartWidget
{
    protected ?string $heading = 'Class-wise Attendance (This Month)';

    protected static ?int $sort = 13;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $classes = SchoolClass::orderBy('numeric_level')->get();

        $labels = [];
        $rates = [];

        foreach ($classes as $class) {
            $labels[] = $class->name;

            $totalRecords = AttendanceRecord::query()
                ->where('class_id', $class->id)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->count();

            $presentRecords = AttendanceRecord::query()
                ->where('class_id', $class->id)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])
                ->count();

            $rate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0;
            $rates[] = $rate;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Attendance %',
                    'data' => $rates,
                    'backgroundColor' => 'rgba(30, 64, 175, 0.2)',
                    'borderColor' => '#1e40af',
                    'pointBackgroundColor' => '#1e40af',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'radar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'r' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'stepSize' => 20,
                    ],
                ],
            ],
        ];
    }
}
