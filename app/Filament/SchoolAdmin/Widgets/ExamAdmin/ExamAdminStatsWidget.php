<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\ExamAdmin;

use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\GeneratedCertificate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExamAdminStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->active_role === 'EXAM_ADMIN';
    }

    protected function getStats(): array
    {
        $activeExams = Exam::active()
            ->whereHas('academicYear', fn ($q) => $q->where('is_current', true))
            ->count();

        $resultsPublished = Exam::where('publish_results', true)->count();

        $pendingMarks = ExamSchedule::whereDoesntHave('marks')->count();

        $reportCards = GeneratedCertificate::where('certificate_number', 'like', 'RC-%')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            Stat::make('Active Exams', $activeExams)
                ->description('Current academic year')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Results Published', $resultsPublished)
                ->description('Total published')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Pending Marks Entry', $pendingMarks)
                ->description('Schedules without marks')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($pendingMarks > 0 ? 'warning' : 'success'),

            Stat::make('Report Cards Generated', $reportCards)
                ->description('This month')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
