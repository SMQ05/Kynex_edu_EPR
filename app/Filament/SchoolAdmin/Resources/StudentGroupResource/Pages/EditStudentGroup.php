<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentGroup extends EditRecord
{
    protected static string $resource = StudentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
