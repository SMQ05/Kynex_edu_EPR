<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\SubscriptionPlanResource\Pages;

use App\Filament\SaasAdmin\Resources\SubscriptionPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionPlans extends ListRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
