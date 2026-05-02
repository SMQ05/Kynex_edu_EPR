<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StaffAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffAttendanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffAttendance extends CreateRecord
{
    protected static string $resource = StaffAttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
