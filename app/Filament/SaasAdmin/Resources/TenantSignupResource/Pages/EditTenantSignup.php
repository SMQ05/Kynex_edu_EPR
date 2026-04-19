<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantSignupResource\Pages;

use App\Filament\SaasAdmin\Resources\TenantSignupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenantSignup extends EditRecord
{
    protected static string $resource = TenantSignupResource::class;

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
