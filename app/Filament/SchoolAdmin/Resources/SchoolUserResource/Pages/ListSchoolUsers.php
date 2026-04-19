<?php

namespace App\Filament\SchoolAdmin\Resources\SchoolUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\SchoolUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolUsers extends ListRecords
{
    protected static string $resource = SchoolUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
