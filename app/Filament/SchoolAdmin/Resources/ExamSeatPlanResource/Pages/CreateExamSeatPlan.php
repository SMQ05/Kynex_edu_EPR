<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource;
use App\Models\Tenant\ExamSeatPlan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExamSeatPlan extends CreateRecord
{
    protected static string $resource = ExamSeatPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $state = $this->form->getState();

        if (empty($state['exam_id']) || empty($state['student_id'])) {
            return;
        }

        if (ExamSeatPlan::withoutTrashed()
            ->where('exam_id', $state['exam_id'])
            ->where('student_id', $state['student_id'])
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
