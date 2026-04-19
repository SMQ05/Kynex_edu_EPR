<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantSignupResource\Pages;

use App\Filament\SaasAdmin\Resources\TenantSignupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantSignup extends CreateRecord
{
    protected static string $resource = TenantSignupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
