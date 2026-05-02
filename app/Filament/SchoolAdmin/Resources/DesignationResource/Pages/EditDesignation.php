<?php

namespace App\Filament\SchoolAdmin\Resources\DesignationResource\Pages;

use App\Filament\SchoolAdmin\Resources\DesignationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDesignation extends EditRecord
{
    protected static string $resource = DesignationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
