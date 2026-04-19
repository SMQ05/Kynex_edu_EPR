<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StaffAttendanceResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffAttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffAttendance extends EditRecord
{
    protected static string $resource = StaffAttendanceResource::class;

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
