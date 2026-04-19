<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantResource\Pages;

use App\Filament\SaasAdmin\Resources\TenantResource;
use App\Filament\SaasAdmin\Resources\TenantResource\Widgets\TenantStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            TenantStatsOverview::class,
        ];
    }
}
