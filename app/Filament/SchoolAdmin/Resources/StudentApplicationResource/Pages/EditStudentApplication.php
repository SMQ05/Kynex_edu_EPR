<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentApplicationResource;
use Filament\Resources\Pages\EditRecord;

class EditStudentApplication extends EditRecord
{
    protected static string $resource = StudentApplicationResource::class;
}
