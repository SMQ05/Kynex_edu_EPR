<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SyllabusResource\Pages;

use App\Filament\SchoolAdmin\Resources\SyllabusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSyllabus extends EditRecord
{
    protected static string $resource = SyllabusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
        protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
