<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudyMaterialResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudyMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudyMaterial extends CreateRecord
{
    protected static string $resource = StudyMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
