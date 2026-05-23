<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdmissionCriteria extends ListRecords
{
    protected static string $resource = AdmissionCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
