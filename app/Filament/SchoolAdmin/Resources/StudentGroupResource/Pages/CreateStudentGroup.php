<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentGroup extends CreateRecord
{
    protected static string $resource = StudentGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
