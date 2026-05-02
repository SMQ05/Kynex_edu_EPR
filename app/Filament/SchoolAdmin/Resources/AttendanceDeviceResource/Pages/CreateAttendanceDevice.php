<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AttendanceDeviceResource\Pages;

use App\Filament\SchoolAdmin\Resources\AttendanceDeviceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceDevice extends CreateRecord
{
    protected static string $resource = AttendanceDeviceResource::class;
}
