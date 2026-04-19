<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SectionResource\Pages;

use App\Filament\SchoolAdmin\Resources\SectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
