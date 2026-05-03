<?php

namespace App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsGalleryAlbums extends ListRecords
{
    protected static string $resource = CmsGalleryAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Album')->icon('heroicon-o-plus'),
        ];
    }
}
