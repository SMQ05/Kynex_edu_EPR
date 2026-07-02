<?php

namespace App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsGalleryAlbum extends CreateRecord
{
    protected static string $resource = CmsGalleryAlbumResource::class;
        protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
