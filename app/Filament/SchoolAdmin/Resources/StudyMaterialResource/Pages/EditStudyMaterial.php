<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudyMaterialResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudyMaterialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudyMaterial extends EditRecord
{
    protected static string $resource = StudyMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
