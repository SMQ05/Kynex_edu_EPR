<?php

namespace App\Filament\SchoolAdmin\Resources\BehaviorIncidentResource\Pages;

use App\Filament\SchoolAdmin\Resources\BehaviorIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBehaviorIncident extends EditRecord
{
    protected static string $resource = BehaviorIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
