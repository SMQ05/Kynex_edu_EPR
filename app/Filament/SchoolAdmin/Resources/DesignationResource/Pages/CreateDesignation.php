<?php

namespace App\Filament\SchoolAdmin\Resources\DesignationResource\Pages;

use App\Filament\SchoolAdmin\Resources\DesignationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDesignation extends CreateRecord
{
    protected static string $resource = DesignationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
