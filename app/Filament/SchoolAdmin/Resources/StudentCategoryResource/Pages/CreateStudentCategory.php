<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentCategoryResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentCategory extends CreateRecord
{
    protected static string $resource = StudentCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
