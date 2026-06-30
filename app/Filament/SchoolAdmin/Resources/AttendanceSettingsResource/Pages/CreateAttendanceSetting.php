<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource\Pages;

use App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceSetting extends CreateRecord
{
    protected static string $resource = AttendanceSettingsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
