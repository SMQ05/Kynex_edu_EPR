<?php

namespace App\Filament\SchoolAdmin\Resources\CmsSliderResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsSliderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsSliders extends ListRecords
{
    protected static string $resource = CmsSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Slider')->icon('heroicon-o-plus'),
        ];
    }
}
