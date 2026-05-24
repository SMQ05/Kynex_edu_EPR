<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Attendance Trend Chart — School Admin Analytics
 *
 * Line chart showing daily attendance percentage for the last 30 school days.
 */
class AttendanceTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Attendance Trend (Last 30 Days)';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getData(): array
    {
        $totalStudents = Student::where('status', 'enrolled')->count();

        if ($totalStudents === 0) {
            return ['datasets' => [], 'labels' => []];
        }

        $dates = collect();
        $presentPct = collect();
        $absentPct = collect();

        // Get last 30 dates that have attendance records
        $attendanceDates = AttendanceRecord::query()
            ->select('date')
            ->distinct()
            ->orderByDesc('date')
            ->limit(30)
            ->pluck('date')
            ->reverse()
            ->values();

        foreach ($attendanceDates as $date) {
            $dateObj = Carbon::parse($date);
            $dates->push($dateObj->format('d M'));

            $dayRecords = AttendanceRecord::where('date', $date);
            $presentCount = (clone $dayRecords)
                ->whereIn('status', [
                    AttendanceStatus::Present,
                    AttendanceStatus::Late,
                ])
                ->count();

            $totalMarked = (clone $dayRecords)->count();

            $pct = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;
            $presentPct->push($pct);
            $absentPct->push(round(100 - $pct, 1));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Present %',
                    'data' => $presentPct->toArray(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Absent %',
                    'data' => $absentPct->toArray(),
                    'borderColor' => '#dc2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $dates->toArray(),
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
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }",
                    ],
                ],
            ],
        ];
    }
}
