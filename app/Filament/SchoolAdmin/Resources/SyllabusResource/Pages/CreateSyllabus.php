<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SyllabusResource\Pages;

use App\Filament\SchoolAdmin\Resources\SyllabusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSyllabus extends CreateRecord
{
    protected static string $resource = SyllabusResource::class;
}
