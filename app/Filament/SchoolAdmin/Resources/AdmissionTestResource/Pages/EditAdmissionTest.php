<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionTestResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionTestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionTest extends EditRecord
{
    protected static string $resource = AdmissionTestResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
