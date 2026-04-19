<?php

namespace App\Filament\SchoolAdmin\Resources\SchoolUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\SchoolUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolUser extends CreateRecord
{
    protected static string $resource = SchoolUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
