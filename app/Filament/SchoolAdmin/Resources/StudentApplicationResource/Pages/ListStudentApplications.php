<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentApplications extends ListRecords
{
    protected static string $resource = StudentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
