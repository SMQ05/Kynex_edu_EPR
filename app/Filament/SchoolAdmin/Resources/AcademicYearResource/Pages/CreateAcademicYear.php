<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AcademicYearResource\Pages;

use App\Filament\SchoolAdmin\Resources\AcademicYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicYear extends CreateRecord
{
    protected static string $resource = AcademicYearResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AcademicYearResource::ensureWeightsSumTo100($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
