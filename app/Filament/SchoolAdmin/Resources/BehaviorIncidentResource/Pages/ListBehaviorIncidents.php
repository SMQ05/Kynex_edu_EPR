<?php

namespace App\Filament\SchoolAdmin\Resources\BehaviorIncidentResource\Pages;

use App\Filament\SchoolAdmin\Resources\BehaviorIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBehaviorIncidents extends ListRecords
{
    protected static string $resource = BehaviorIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
