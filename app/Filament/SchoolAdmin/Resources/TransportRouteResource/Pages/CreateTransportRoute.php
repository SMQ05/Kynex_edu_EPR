<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TransportRouteResource\Pages;

use App\Filament\SchoolAdmin\Resources\TransportRouteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransportRoute extends CreateRecord
{
    protected static string $resource = TransportRouteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
