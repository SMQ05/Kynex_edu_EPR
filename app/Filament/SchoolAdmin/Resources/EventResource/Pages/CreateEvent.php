<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\EventResource\Pages;

use App\Filament\SchoolAdmin\Resources\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
