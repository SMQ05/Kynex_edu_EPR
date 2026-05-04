<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamResult;
use Filament\Widgets\ChartWidget;

/**
 * Exam Performance Chart — School Admin Analytics
 *
 * Horizontal bar chart showing average percentage per exam,
 * with pass/fail count overlay.
 */
class ExamPerformanceWidget extends ChartWidget
{
    protected ?string $heading = 'Exam Performance Summary';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get last 6 exams
        $exams = Exam::query()
            ->where('status', 'published')
            ->orderByDesc('start_date')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $labels = $exams->pluck('name')->toArray();
        $avgPercentages = [];
        $passRates = [];

        foreach ($exams as $exam) {
            $results = ExamResult::where('exam_id', $exam->id);

            $avg = (clone $results)->avg('percentage') ?? 0;
            $avgPercentages[] = round($avg, 1);

            $totalResults = (clone $results)->count();
            $passedResults = (clone $results)->where('status', 'passed')->count();

            $passRate = $totalResults > 0 ? round(($passedResults / $totalResults) * 100, 1) : 0;
            $passRates[] = $passRate;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Avg. Percentage',
                    'data' => $avgPercentages,
                    'backgroundColor' => 'rgba(30, 64, 175, 0.7)',
                    'borderColor' => '#1e40af',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pass Rate %',
                    'data' => $passRates,
                    'backgroundColor' => 'rgba(22, 163, 74, 0.7)',
                    'borderColor' => '#16a34a',
                    'borderWidth' => 1,
                ],
            ],
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
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'x' => [
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
