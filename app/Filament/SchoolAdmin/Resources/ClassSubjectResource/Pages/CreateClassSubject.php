<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassSubjectResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassSubjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSubject extends CreateRecord
{
    protected static string $resource = ClassSubjectResource::class;
}
