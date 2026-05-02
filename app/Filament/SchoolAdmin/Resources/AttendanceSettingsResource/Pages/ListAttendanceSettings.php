<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource\Pages;

use App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceSettings extends ListRecords
{
    protected static string $resource = AttendanceSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
