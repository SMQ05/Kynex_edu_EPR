<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource\Pages;

use App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFrontOfficeReference extends CreateRecord
{
    protected static string $resource = FrontOfficeReferenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
