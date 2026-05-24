<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Pages;

use App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeacherEvaluations extends ListRecords
{
    protected static string $resource = TeacherEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
