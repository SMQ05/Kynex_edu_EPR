<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HostelBuildingResource\Pages;

use App\Filament\SchoolAdmin\Resources\HostelBuildingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostelBuildings extends ListRecords
{
    protected static string $resource = HostelBuildingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
