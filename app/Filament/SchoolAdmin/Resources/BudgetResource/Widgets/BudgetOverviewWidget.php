<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BudgetResource\Widgets;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Budget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BudgetOverviewWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        $budgets = $currentYear
            ? Budget::where('academic_year_id', $currentYear->id)->get()
            : collect();

        $totalBudgeted = $budgets->sum('budgeted_amount_paisas');
        $totalSpent = $budgets->sum('spent_amount_paisas');
        $remaining = $totalBudgeted - $totalSpent;
        $overBudgetCount = $budgets->filter(fn ($b) => $b->spent_amount_paisas > $b->budgeted_amount_paisas)->count();

        return [
            Stat::make('Total Budgeted', 'PKR ' . number_format($totalBudgeted / 100, 2))
                ->description($currentYear?->name ?? 'No active year')
                ->icon('heroicon-o-calculator')
                ->color('primary'),

            Stat::make('Total Spent', 'PKR ' . number_format($totalSpent / 100, 2))
                ->description($totalBudgeted > 0
                    ? round(($totalSpent / $totalBudgeted) * 100, 1) . '% utilization'
                    : '0% utilization')
                ->icon('heroicon-o-banknotes')
                ->color($totalBudgeted > 0 && ($totalSpent / $totalBudgeted) > 0.9 ? 'danger' : 'warning'),

            Stat::make('Remaining', 'PKR ' . number_format($remaining / 100, 2))
                ->description($remaining < 0 ? 'Over budget!' : 'Available')
                ->icon('heroicon-o-arrow-trending-down')
                ->color($remaining < 0 ? 'danger' : 'success'),

            Stat::make('Over-Budget Items', (string) $overBudgetCount)
                ->description('Budgets exceeding limit')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($overBudgetCount > 0 ? 'danger' : 'success'),
        ];
    }
}
