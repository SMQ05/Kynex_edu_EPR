<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassRoutineResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassRoutineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassRoutine extends CreateRecord
{
    protected static string $resource = ClassRoutineResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
