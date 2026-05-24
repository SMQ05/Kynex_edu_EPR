<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\LessonResource\Pages;

use App\Filament\SchoolAdmin\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
