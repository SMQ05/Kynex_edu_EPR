<?php

namespace App\Filament\SchoolAdmin\Resources\LeaveTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\LeaveTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveType extends CreateRecord
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
