<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource\Pages;

use App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFrontOfficeReference extends EditRecord
{
    protected static string $resource = FrontOfficeReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
