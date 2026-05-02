<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentApplicationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateStudentApplication extends CreateRecord
{
    protected static string $resource = StudentApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['public_token'] = Str::random(40);
        $data['status'] = $data['status'] ?? 'submitted';
        return $data;
    }
}
