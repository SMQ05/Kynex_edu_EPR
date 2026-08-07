<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamAttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExamAttendanceRecord extends EditRecord
{
    protected static string $resource = ExamAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
