<?php

namespace App\Filament\SchoolAdmin\Resources\CmsAnnouncementResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsAnnouncements extends ListRecords
{
    protected static string $resource = CmsAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Announcement')->icon('heroicon-o-plus'),
        ];
    }
}
