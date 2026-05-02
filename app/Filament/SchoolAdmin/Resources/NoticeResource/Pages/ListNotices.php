<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\NoticeResource\Pages;

use App\Filament\SchoolAdmin\Resources\NoticeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotices extends ListRecords
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
