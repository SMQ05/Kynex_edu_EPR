<?php

namespace App\Filament\SchoolAdmin\Resources\HealthRecordResource\Pages;

use App\Filament\SchoolAdmin\Resources\HealthRecordResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHealthRecord extends CreateRecord
{
    protected static string $resource = HealthRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = Auth::guard('school_users')->id();

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
