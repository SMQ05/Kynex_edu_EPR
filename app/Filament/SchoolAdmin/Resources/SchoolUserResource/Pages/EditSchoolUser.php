<?php

namespace App\Filament\SchoolAdmin\Resources\SchoolUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\SchoolUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolUser extends EditRecord
{
    protected static string $resource = SchoolUserResource::class;

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
