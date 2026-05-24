<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ContentTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\ContentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentType extends CreateRecord
{
    protected static string $resource = ContentTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
