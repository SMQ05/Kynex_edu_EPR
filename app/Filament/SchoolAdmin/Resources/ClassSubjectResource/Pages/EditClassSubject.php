<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassSubjectResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassSubject extends EditRecord
{
    protected static string $resource = ClassSubjectResource::class;

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
