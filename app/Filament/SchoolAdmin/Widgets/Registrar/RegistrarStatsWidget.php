<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\Registrar;

use App\Models\Tenant\GeneratedCertificate;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentPromotion;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrarStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->active_role === 'REGISTRAR';
    }

    protected function getStats(): array
    {
        $newAdmissions = Student::whereMonth('admission_date', now()->month)
            ->whereYear('admission_date', now()->year)
            ->count();

        $pendingDocs = Student::where('status', 'enrolled')
            ->whereDoesntHave('documents')
            ->count();

        $promotionsPending = StudentPromotion::whereNull('promoted_at')
            ->count();

        $certificatesIssued = GeneratedCertificate::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            Stat::make('New Admissions', $newAdmissions)
                ->description('This month')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Pending Documents', $pendingDocs)
                ->description('Students with no docs')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($pendingDocs > 0 ? 'warning' : 'success'),

            Stat::make('Promotions Pending', $promotionsPending)
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color($promotionsPending > 0 ? 'warning' : 'gray'),

            Stat::make('Certificates Issued', $certificatesIssued)
                ->description('This month')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary'),
        ];
    }
}
