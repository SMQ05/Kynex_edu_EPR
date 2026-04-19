<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\VehicleResource\Pages;

use App\Filament\SchoolAdmin\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
