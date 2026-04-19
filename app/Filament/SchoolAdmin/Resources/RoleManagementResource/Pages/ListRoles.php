<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\RoleManagementResource\Pages;

use App\Filament\SchoolAdmin\Resources\RoleManagementResource;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
