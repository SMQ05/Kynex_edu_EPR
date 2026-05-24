<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource;
use Filament\Actions\DeleteAction;
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
}
