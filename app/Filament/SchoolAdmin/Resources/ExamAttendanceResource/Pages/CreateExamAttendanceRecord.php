<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamAttendanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamAttendanceRecord extends CreateRecord
{
    protected static string $resource = ExamAttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
