<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PiiAccessLogResource\Pages;

use App\Filament\SchoolAdmin\Resources\PiiAccessLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPiiAccessLogs extends ListRecords
{
    protected static string $resource = PiiAccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
