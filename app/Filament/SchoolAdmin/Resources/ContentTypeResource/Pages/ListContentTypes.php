<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ContentTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\ContentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentTypes extends ListRecords
{
    protected static string $resource = ContentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
