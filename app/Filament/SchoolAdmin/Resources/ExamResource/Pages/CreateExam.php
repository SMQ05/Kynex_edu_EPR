<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
