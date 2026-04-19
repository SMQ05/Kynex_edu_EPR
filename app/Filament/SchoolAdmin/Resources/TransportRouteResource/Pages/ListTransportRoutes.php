<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TransportRouteResource\Pages;

use App\Filament\SchoolAdmin\Resources\TransportRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransportRoutes extends ListRecords
{
    protected static string $resource = TransportRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
