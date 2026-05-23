<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmissionCriteria extends CreateRecord
{
    protected static string $resource = AdmissionCriteriaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        AdmissionCriteriaResource::assertWeightagesSumTo100($data);
        AdmissionCriteriaResource::assertScopeIsValid($data);

        // The "classes" multi-select feeds the belongsToMany relationship,
        // not a column on admission_criteria itself, so strip it before
        // the row is inserted. Filament's relationship handling syncs the
        // pivot from $this->record after create.
        unset($data['classes']);

        return $data;
    }
}
