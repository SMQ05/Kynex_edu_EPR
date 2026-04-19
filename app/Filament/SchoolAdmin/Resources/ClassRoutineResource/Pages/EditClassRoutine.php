<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassRoutineResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassRoutineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassRoutine extends EditRecord
{
    protected static string $resource = ClassRoutineResource::class;

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
