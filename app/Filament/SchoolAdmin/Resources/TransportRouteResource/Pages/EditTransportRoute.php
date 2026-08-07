<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TransportRouteResource\Pages;

use App\Filament\SchoolAdmin\Resources\TransportRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransportRoute extends EditRecord
{
    protected static string $resource = TransportRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
