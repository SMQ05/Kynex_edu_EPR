<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CustomFieldResource\Pages;

use App\Filament\SchoolAdmin\Resources\CustomFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomField extends CreateRecord
{
    protected static string $resource = CustomFieldResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
