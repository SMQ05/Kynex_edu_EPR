<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\SubscriptionPlanResource\Pages;

use App\Filament\SaasAdmin\Resources\SubscriptionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPlan extends EditRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return SubscriptionPlanResource::mutateFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
