<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\VehicleResource\Pages;

use App\Filament\SchoolAdmin\Resources\VehicleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
