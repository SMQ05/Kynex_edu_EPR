<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\Pages;

use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeworkAssignment extends CreateRecord
{
    protected static string $resource = HomeworkAssignmentResource::class;
}
