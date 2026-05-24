<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HolidayResource\Pages;

use App\Filament\SchoolAdmin\Resources\HolidayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    protected static string $resource = HolidayResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
