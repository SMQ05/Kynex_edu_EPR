<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FeeMasterResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeeMasters extends ListRecords
{
    protected static string $resource = FeeMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
