<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource\Pages;

use App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFrontOfficeReferences extends ListRecords
{
    protected static string $resource = FrontOfficeReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
