<?php

namespace App\Filament\SchoolAdmin\Resources\StaffResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffResource;
use App\Filament\SchoolAdmin\Support\CustomFieldsForm;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    /** Keep the non-model custom_fields key out of record creation. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['custom_fields']);

        return $data;
    }

    protected function afterCreate(): void
    {
        CustomFieldsForm::save('staff', $this->record->id, $this->data['custom_fields'] ?? []);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
