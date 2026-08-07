<?php

namespace App\Filament\SchoolAdmin\Resources\IdCardTemplateResource\Pages;

use App\Filament\SchoolAdmin\Resources\IdCardTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIdCardTemplate extends EditRecord
{
    protected static string $resource = IdCardTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
