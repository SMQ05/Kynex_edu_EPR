<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionCriteria extends EditRecord
{
    protected static string $resource = AdmissionCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        AdmissionCriteriaResource::assertWeightagesSumTo100($data);
        AdmissionCriteriaResource::assertScopeIsValid($data, $this->record?->getKey());

        // Strip the relationship key — the belongsToMany multi-select is
        // synced separately by Filament after the model save.
        unset($data['classes']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
