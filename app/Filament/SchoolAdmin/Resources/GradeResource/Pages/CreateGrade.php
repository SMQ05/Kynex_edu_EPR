<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\GradeResource\Pages;

use App\Filament\SchoolAdmin\Resources\GradeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGrade extends CreateRecord
{
    protected static string $resource = GradeResource::class;
}
