<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\InventorySaleResource\Pages;

use App\Filament\SchoolAdmin\Resources\InventorySaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventorySales extends ListRecords
{
    protected static string $resource = InventorySaleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New sale')];
    }
}
