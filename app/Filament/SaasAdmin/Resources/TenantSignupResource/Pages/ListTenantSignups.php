<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantSignupResource\Pages;

use App\Filament\SaasAdmin\Resources\TenantSignupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenantSignups extends ListRecords
{
    protected static string $resource = TenantSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
