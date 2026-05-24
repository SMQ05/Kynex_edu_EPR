<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['custom_fields'] = \App\Filament\SchoolAdmin\Support\CustomFieldsForm::load('student', $this->record->id);

        return $data;
    }

    protected function afterSave(): void
    {
        \App\Filament\SchoolAdmin\Support\CustomFieldsForm::save('student', $this->record->id, $this->data['custom_fields'] ?? []);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
