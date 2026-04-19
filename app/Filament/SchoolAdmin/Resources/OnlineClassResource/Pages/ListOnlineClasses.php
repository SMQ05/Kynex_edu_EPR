<?php

namespace App\Filament\SchoolAdmin\Resources\OnlineClassResource\Pages;

use App\Filament\SchoolAdmin\Resources\OnlineClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOnlineClasses extends ListRecords
{
    protected static string $resource = OnlineClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
