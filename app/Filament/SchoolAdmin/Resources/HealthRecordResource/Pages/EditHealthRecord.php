<?php

namespace App\Filament\SchoolAdmin\Resources\HealthRecordResource\Pages;

use App\Filament\SchoolAdmin\Resources\HealthRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHealthRecord extends EditRecord
{
    protected static string $resource = HealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
