<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamSeatPlan extends CreateRecord
{
    protected static string $resource = ExamSeatPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
