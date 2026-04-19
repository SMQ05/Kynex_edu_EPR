<?php

namespace App\Filament\SchoolAdmin\Resources\BehaviorIncidentResource\Pages;

use App\Filament\SchoolAdmin\Resources\BehaviorIncidentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBehaviorIncident extends CreateRecord
{
    protected static string $resource = BehaviorIncidentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reported_by'] = Auth::id();

        return $data;
    }
}
