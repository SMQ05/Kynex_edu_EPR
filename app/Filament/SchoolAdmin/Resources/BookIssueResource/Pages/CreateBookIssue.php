<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BookIssueResource\Pages;

use App\Filament\SchoolAdmin\Resources\BookIssueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookIssue extends CreateRecord
{
    protected static string $resource = BookIssueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['issued_by'] = auth()->guard('school_users')->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
