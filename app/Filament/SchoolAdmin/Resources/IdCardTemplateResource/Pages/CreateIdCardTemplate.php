<?php

namespace App\Filament\SchoolAdmin\Resources\IdCardTemplateResource\Pages;

use App\Filament\SchoolAdmin\Resources\IdCardTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIdCardTemplate extends CreateRecord
{
    protected static string $resource = IdCardTemplateResource::class;
    
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
