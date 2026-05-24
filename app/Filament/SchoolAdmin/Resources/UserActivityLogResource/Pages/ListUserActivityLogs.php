<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\UserActivityLogResource\Pages;

use App\Filament\SchoolAdmin\Resources\UserActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListUserActivityLogs extends ListRecords
{
    protected static string $resource = UserActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
