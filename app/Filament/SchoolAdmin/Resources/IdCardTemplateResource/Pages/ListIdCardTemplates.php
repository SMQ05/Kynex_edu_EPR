<?php

namespace App\Filament\SchoolAdmin\Resources\IdCardTemplateResource\Pages;

use App\Filament\SchoolAdmin\Resources\IdCardTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIdCardTemplates extends ListRecords
{
    protected static string $resource = IdCardTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
