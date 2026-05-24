<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubjectAttendanceRecord extends EditRecord
{
    protected static string $resource = SubjectAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
