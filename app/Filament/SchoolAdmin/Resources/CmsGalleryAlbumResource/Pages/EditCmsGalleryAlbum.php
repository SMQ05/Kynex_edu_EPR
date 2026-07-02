<?php

namespace App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource;
use Filament\Resources\Pages\EditRecord;

class EditCmsGalleryAlbum extends EditRecord
{
    protected static string $resource = CmsGalleryAlbumResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
