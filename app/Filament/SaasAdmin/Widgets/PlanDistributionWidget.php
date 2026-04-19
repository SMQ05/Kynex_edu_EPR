<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\TenantStatus;
use App\Models\SubscriptionPlan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PlanDistributionWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Plan Distribution';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SubscriptionPlan::query()
                    ->where('is_active', true)
                    ->withCount(['tenants' => function (Builder $query): void {
                        $query->whereIn('status', [
                            TenantStatus::Active,
                            TenantStatus::Trial,
                        ]);
                    }])
                    ->orderByDesc('tenants_count')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plan Name')
                    ->weight('bold')
                    ->icon('heroicon-o-tag'),

                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Billing Type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('base_price_paisas')
                    ->label('Base Price')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 0))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Schools')
                    ->alignEnd()
                    ->badge()
                    ->color('primary'),
            ])
            ->paginated(false);
    }
}
