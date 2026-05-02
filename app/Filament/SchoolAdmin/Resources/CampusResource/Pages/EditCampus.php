<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CampusResource\Pages;

use App\Filament\SchoolAdmin\Resources\CampusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampus extends EditRecord
{
    protected static string $resource = CampusResource::class;

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
