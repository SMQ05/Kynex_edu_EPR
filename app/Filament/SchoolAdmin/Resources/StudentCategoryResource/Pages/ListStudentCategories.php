<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentCategoryResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentCategories extends ListRecords
{
    protected static string $resource = StudentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
