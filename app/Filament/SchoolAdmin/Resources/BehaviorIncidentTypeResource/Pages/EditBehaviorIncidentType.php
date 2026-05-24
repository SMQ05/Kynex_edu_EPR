<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBehaviorIncidentType extends EditRecord
{
    protected static string $resource = BehaviorIncidentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
