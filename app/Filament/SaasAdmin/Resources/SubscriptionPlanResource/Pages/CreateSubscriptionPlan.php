<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\SubscriptionPlanResource\Pages;

use App\Filament\SaasAdmin\Resources\SubscriptionPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionPlan extends CreateRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SubscriptionPlanResource::mutateFormDataBeforeCreate($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
