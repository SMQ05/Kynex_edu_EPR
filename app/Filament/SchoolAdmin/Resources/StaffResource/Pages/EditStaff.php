<?php

namespace App\Filament\SchoolAdmin\Resources\StaffResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffResource;
use App\Filament\SchoolAdmin\Support\CustomFieldsForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['custom_fields'] = CustomFieldsForm::load('staff', $this->record->id);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['custom_fields']);

        return $data;
    }

    protected function afterSave(): void
    {
        CustomFieldsForm::save('staff', $this->record->id, $this->data['custom_fields'] ?? []);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
