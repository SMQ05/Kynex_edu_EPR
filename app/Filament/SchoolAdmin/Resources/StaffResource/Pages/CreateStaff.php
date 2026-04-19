<?php

namespace App\Filament\SchoolAdmin\Resources\StaffResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
