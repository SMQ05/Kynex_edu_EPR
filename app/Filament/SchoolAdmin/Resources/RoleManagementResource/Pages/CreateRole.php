<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\RoleManagementResource\Pages;

use App\Filament\SchoolAdmin\Resources\RoleManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleManagementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'school_users';

        return $data;
    }
}
