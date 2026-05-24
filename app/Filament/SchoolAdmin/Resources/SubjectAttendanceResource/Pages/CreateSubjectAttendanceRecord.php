<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubjectAttendanceRecord extends CreateRecord
{
    protected static string $resource = SubjectAttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
