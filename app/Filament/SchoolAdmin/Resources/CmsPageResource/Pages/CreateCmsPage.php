<?php

namespace App\Filament\SchoolAdmin\Resources\CmsPageResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
