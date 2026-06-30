<?php

namespace App\Filament\SchoolAdmin\Resources\CafeteriaMenuItemResource\Pages;

use App\Filament\SchoolAdmin\Resources\CafeteriaMenuItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCafeteriaMenuItem extends EditRecord
{
    protected static string $resource = CafeteriaMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
