<?php

namespace App\Filament\SchoolAdmin\Resources\CafeteriaMenuItemResource\Pages;

use App\Filament\SchoolAdmin\Resources\CafeteriaMenuItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCafeteriaMenuItems extends ListRecords
{
    protected static string $resource = CafeteriaMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
