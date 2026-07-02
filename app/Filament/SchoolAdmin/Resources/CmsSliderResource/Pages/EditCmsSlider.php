<?php

namespace App\Filament\SchoolAdmin\Resources\CmsSliderResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsSliderResource;
use Filament\Resources\Pages\EditRecord;

class EditCmsSlider extends EditRecord
{
    protected static string $resource = CmsSliderResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
