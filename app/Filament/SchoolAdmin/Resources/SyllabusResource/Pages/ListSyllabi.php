<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SyllabusResource\Pages;

use App\Filament\SchoolAdmin\Resources\SyllabusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSyllabi extends ListRecords
{
    protected static string $resource = SyllabusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
