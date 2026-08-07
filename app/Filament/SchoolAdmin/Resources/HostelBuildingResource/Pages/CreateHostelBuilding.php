<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HostelBuildingResource\Pages;

use App\Filament\SchoolAdmin\Resources\HostelBuildingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHostelBuilding extends CreateRecord
{
    protected static string $resource = HostelBuildingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
