<?php

namespace App\Filament\SchoolAdmin\Resources\FeeGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeeGroups extends ListRecords
{
    protected static string $resource = FeeGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
