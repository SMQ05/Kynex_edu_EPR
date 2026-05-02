<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassSubjectResource\Pages;

use App\Filament\SchoolAdmin\Resources\ClassSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassSubjects extends ListRecords
{
    protected static string $resource = ClassSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
