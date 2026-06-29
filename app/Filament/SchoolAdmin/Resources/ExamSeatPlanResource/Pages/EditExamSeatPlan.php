<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource;
use App\Models\Tenant\ExamSeatPlan;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExamSeatPlan extends EditRecord
{
    protected static string $resource = ExamSeatPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $state = $this->form->getState();

        if (empty($state['exam_id']) || empty($state['student_id'])) {
            return;
        }

        if (ExamSeatPlan::withoutTrashed()
            ->where('exam_id', $state['exam_id'])
            ->where('student_id', $state['student_id'])
            ->whereKeyNot($this->record->getKey())
            ->exists()
        ) {
            Notification::make()
                ->danger()
                ->title('Duplicate Allocation')
                ->body('This student has already been allocated a seat for the selected term. Duplicate allocations are not allowed.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
