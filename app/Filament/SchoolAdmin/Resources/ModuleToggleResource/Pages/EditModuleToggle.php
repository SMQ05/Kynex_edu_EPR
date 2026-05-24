<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ModuleToggleResource\Pages;

use App\Filament\SchoolAdmin\Resources\ModuleToggleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModuleToggle extends EditRecord
{
    protected static string $resource = ModuleToggleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
