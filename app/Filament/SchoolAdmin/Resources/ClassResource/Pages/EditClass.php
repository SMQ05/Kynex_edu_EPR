<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClass extends EditRecord
{
    protected static string $resource = ClassResource::class;

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
