<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\Pages;

use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeworkAssignments extends ListRecords
{
    protected static string $resource = HomeworkAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
