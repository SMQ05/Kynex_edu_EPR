<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassroomResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassroomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassroom extends EditRecord
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
