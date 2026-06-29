<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamResource;
use App\Jobs\NotifyResultPublished;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Phase 9.5 — Dispatch NotifyResultPublished when publish_results is toggled ON.
     * Only dispatches when the flag changes from false to true, not on every save.
     */
    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->wasChanged('publish_results') && $record->publish_results === true) {
            NotifyResultPublished::dispatch($record);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
