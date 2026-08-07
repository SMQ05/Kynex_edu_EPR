<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\OnlineExamResource\Pages;

use App\Filament\SchoolAdmin\Resources\OnlineExamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOnlineExam extends EditRecord
{
    protected static string $resource = OnlineExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Keep total_marks consistent with attached questions.
        $this->record->load('questions');
        $this->record->recomputeTotalMarks();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
