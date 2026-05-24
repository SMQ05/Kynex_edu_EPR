<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamSeatPlans extends ListRecords
{
    protected static string $resource = ExamSeatPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
