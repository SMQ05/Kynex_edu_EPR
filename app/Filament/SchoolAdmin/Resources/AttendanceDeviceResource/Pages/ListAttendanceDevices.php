<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AttendanceDeviceResource\Pages;

use App\Filament\SchoolAdmin\Resources\AttendanceDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceDevices extends ListRecords
{
    protected static string $resource = AttendanceDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
