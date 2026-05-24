<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsContactMessageResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsContactMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsContactMessage extends CreateRecord
{
    protected static string $resource = CmsContactMessageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
