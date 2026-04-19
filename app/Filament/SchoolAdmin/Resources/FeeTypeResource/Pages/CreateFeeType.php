<?php

namespace App\Filament\SchoolAdmin\Resources\FeeTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeeType extends CreateRecord
{
    protected static string $resource = FeeTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
