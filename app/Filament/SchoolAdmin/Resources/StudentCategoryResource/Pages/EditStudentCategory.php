<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentCategoryResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentCategory extends EditRecord
{
    protected static string $resource = StudentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
