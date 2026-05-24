<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBehaviorIncidentType extends CreateRecord
{
    protected static string $resource = BehaviorIncidentTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
