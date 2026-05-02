<?php

namespace App\Filament\SchoolAdmin\Resources\FeeGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeGroup extends EditRecord
{
    protected static string $resource = FeeGroupResource::class;

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
