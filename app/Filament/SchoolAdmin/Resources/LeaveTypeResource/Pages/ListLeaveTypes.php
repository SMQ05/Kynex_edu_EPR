<?php

namespace App\Filament\SchoolAdmin\Resources\LeaveTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\LeaveTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveTypes extends ListRecords
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
