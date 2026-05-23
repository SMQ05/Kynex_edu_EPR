<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionTestResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionTestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdmissionTests extends ListRecords
{
    protected static string $resource = AdmissionTestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
