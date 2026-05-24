<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ModuleToggleResource\Pages;

use App\Filament\SchoolAdmin\Resources\ModuleToggleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModuleToggle extends CreateRecord
{
    protected static string $resource = ModuleToggleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
