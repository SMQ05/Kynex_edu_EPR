<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClass extends CreateRecord
{
    protected static string $resource = ClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
