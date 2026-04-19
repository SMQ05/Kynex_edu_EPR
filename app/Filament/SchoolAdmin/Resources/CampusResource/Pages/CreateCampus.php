<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CampusResource\Pages;

use App\Filament\SchoolAdmin\Resources\CampusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampus extends CreateRecord
{
    protected static string $resource = CampusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
