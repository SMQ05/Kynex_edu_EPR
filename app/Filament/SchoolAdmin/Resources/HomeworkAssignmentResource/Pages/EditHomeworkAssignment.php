<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\Pages;

use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeworkAssignment extends EditRecord
{
    protected static string $resource = HomeworkAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
