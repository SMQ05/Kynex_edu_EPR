<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CampusResource\Pages;

use App\Filament\SchoolAdmin\Resources\CampusResource;
use Filament\Resources\Pages\ListRecords;

class ListCampuses extends ListRecords
{
    protected static string $resource = CampusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
