<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamAttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamAttendanceRecords extends ListRecords
{
    protected static string $resource = ExamAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
