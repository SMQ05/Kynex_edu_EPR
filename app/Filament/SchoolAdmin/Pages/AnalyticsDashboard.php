<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Widgets\AttendanceTrendWidget;
use App\Filament\SchoolAdmin\Widgets\ClassAttendanceComparisonWidget;
use App\Filament\SchoolAdmin\Widgets\EnrolmentByClassWidget;
use App\Filament\SchoolAdmin\Widgets\ExamPerformanceWidget;
use App\Filament\SchoolAdmin\Widgets\FeeCollectionTrendWidget;
use App\Filament\SchoolAdmin\Widgets\FeeStatusDistributionWidget;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;

/**
 * Analytics Dashboard Page — School Admin
 *
 * A dedicated analytics page with comprehensive school-level charts:
 * - Attendance trends (30 days line)
 * - Class-wise attendance radar
 * - Fee collection bar chart
 * - Fee status doughnut
 * - Exam performance horizontal bars
 * - Enrolment stacked bars by gender
 *
 * Only accessible to SCHOOL_ADMIN and INSTITUTE_HEAD roles.
 */
class AnalyticsDashboard extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_academic_analytics';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'School Analytics';

    protected static ?int $navigationSort = 2;

    protected static string | \UnitEnum | null $navigationGroup = 'Reports';

    protected string $view = 'filament.school-admin.pages.analytics-dashboard';

    public static function canAccess(): bool
    {
        $role = auth()->user()?->active_role
            ?? auth()->user()?->roles?->first()?->name;

        return in_array($role, ['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']);
    }

    public function getHeaderWidgets(): array
    {
        return [
            AttendanceTrendWidget::class,
            ClassAttendanceComparisonWidget::class,
            FeeCollectionTrendWidget::class,
            FeeStatusDistributionWidget::class,
            ExamPerformanceWidget::class,
            EnrolmentByClassWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }
}
