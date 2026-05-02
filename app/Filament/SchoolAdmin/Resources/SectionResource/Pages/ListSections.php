<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SectionResource\Pages;

use App\Filament\SchoolAdmin\Resources\SectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSections extends ListRecords
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
