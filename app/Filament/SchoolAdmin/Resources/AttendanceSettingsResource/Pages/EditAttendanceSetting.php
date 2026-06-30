<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource\Pages;

use App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceSetting extends EditRecord
{
    protected static string $resource = AttendanceSettingsResource::class;

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
