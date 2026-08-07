<?php

namespace App\Filament\SchoolAdmin\Resources\CmsSliderResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsSliderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsSlider extends CreateRecord
{
    protected static string $resource = CmsSliderResource::class;

        protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
