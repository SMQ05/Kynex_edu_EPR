<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsContactMessageResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsContactMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsContactMessages extends ListRecords
{
    protected static string $resource = CmsContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
