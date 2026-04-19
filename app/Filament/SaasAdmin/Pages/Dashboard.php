<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Pages;

use App\Filament\SaasAdmin\Widgets\AiUsageAnalyticsWidget;
use App\Filament\SaasAdmin\Widgets\InvoicesDueWidget;
use App\Filament\SaasAdmin\Widgets\LatestSignupsWidget;
use App\Filament\SaasAdmin\Widgets\PlanDistributionWidget;
use App\Filament\SaasAdmin\Widgets\RevenueTrendWidget;
use App\Filament\SaasAdmin\Widgets\StatsOverviewWidget;
use App\Filament\SaasAdmin\Widgets\TenantGrowthWidget;
use App\Filament\SaasAdmin\Widgets\TenantStatusPieWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            LatestSignupsWidget::class,
            InvoicesDueWidget::class,
            PlanDistributionWidget::class,
            RevenueTrendWidget::class,
            TenantGrowthWidget::class,
            TenantStatusPieWidget::class,
            AiUsageAnalyticsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }
}
