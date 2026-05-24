<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentGroups extends ListRecords
{
    protected static string $resource = StudentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
