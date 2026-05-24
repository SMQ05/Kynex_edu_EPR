<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassroomResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassroomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassrooms extends ListRecords
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
