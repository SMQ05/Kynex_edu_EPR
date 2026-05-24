<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBehaviorIncidentTypes extends ListRecords
{
    protected static string $resource = BehaviorIncidentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
