<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StaffAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffAttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffAttendance extends ListRecords
{
    protected static string $resource = StaffAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
