<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\LessonResource\Pages;

use App\Filament\SchoolAdmin\Resources\LessonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
