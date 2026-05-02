<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\TenantStatus;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalSchools = Tenant::count();
        $activeSchools = Tenant::where('status', TenantStatus::Active)->count();
        $trialSchools = Tenant::where('status', TenantStatus::Trial)->count();
        $suspendedSchools = Tenant::where('status', TenantStatus::Suspended)->count();

        // Estimated MRR: sum of base_price for all active/trial tenants' plans
        $estimatedMrrPaisas = Tenant::query()
            ->whereIn('status', [TenantStatus::Active, TenantStatus::Trial])
            ->whereNotNull('plan_id')
            ->get()
            ->sum(function (Tenant $tenant) {
                return $tenant->plan?->base_price_paisas ?? 0;
            });

        $estimatedMrrPkr = number_format($estimatedMrrPaisas / 100, 0);

        return [
            Stat::make('Total Schools', $totalSchools)
                ->description('All registered schools')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Active', $activeSchools)
                ->description('Paid & active')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('On Trial', $trialSchools)
                ->description('Currently trialing')
                ->icon('heroicon-o-clock')
                ->color('info'),

            Stat::make('Suspended', $suspendedSchools)
                ->description('Suspended accounts')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Est. MRR', 'PKR ' . $estimatedMrrPkr)
                ->description('Monthly recurring revenue')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
