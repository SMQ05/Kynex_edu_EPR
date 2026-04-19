<?php

namespace App\Filament\SchoolAdmin\Resources\FeeGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeeGroup extends CreateRecord
{
    protected static string $resource = FeeGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
