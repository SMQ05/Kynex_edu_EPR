<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubjectAttendanceRecords extends ListRecords
{
    protected static string $resource = SubjectAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
