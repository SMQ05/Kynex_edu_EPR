<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Enums\StudentGender;
use App\Enums\StudentStatus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use Filament\Widgets\ChartWidget;

/**
 * Student Enrolment by Class — School Admin Analytics
 *
 * Stacked bar chart: student count per class, split by gender.
 */
class EnrolmentByClassWidget extends ChartWidget
{
    protected ?string $heading = 'Enrolment by Class & Gender';

    protected static ?int $sort = 15;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getData(): array
    {
        $classes = SchoolClass::orderBy('numeric_level')->get();

        $labels = [];
        $maleData = [];
        $femaleData = [];
        $otherData = [];

        foreach ($classes as $class) {
            $labels[] = $class->name;

            $base = Student::where('class_id', $class->id)
                ->where('status', StudentStatus::Enrolled);

            $maleData[] = (clone $base)->where('gender', StudentGender::Male)->count();
            $femaleData[] = (clone $base)->where('gender', StudentGender::Female)->count();
            $otherData[] = (clone $base)
                ->whereNotIn('gender', [StudentGender::Male, StudentGender::Female])
                ->count();
        }

        $datasets = [
            [
                'label' => 'Male',
                'data' => $maleData,
                'backgroundColor' => 'rgba(30, 64, 175, 0.7)',
                'borderColor' => '#1e40af',
                'borderWidth' => 1,
            ],
            [
                'label' => 'Female',
                'data' => $femaleData,
                'backgroundColor' => 'rgba(219, 39, 119, 0.7)',
                'borderColor' => '#db2777',
                'borderWidth' => 1,
            ],
        ];

        // Only include "Other" if there are any
        if (array_sum($otherData) > 0) {
            $datasets[] = [
                'label' => 'Other',
                'data' => $otherData,
                'backgroundColor' => 'rgba(107, 114, 128, 0.7)',
                'borderColor' => '#6b7280',
                'borderWidth' => 1,
            ];
        }

        return [
            'datasets' => $datasets,
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
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
