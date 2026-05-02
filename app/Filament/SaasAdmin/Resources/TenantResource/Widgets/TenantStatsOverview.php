<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantResource\Widgets;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total     = Tenant::count();
        $active    = Tenant::where('status', TenantStatus::Active)->count();
        $trial     = Tenant::where('status', TenantStatus::Trial)->count();
        $suspended = Tenant::where('status', TenantStatus::Suspended)->count();

        // Estimate MRR: sum of base_price_paisas for all active tenants' plans
        $mrrPaisas = Tenant::where('status', TenantStatus::Active)
            ->whereHas('plan')
            ->with('plan')
            ->get()
            ->sum(fn (Tenant $t) => $t->plan?->base_price_paisas ?? 0);

        $mrrPkr = number_format($mrrPaisas / 100, 0);

        return [
            Stat::make('Total Schools', (string) $total)
                ->icon('heroicon-o-building-library')
                ->color('primary'),

            Stat::make('Active', (string) $active)
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('On Trial', (string) $trial)
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Suspended', (string) $suspended)
                ->icon('heroicon-o-no-symbol')
                ->color('danger'),

            Stat::make('Est. MRR', "PKR {$mrrPkr}")
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->description('Monthly Recurring Revenue'),
        ];
    }
}
