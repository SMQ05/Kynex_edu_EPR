<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HolidayResource\Pages;

use App\Filament\SchoolAdmin\Resources\HolidayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHolidays extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
