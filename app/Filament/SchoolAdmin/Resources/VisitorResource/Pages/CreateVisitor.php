<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\VisitorResource\Pages;

use App\Filament\SchoolAdmin\Resources\VisitorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisitor extends CreateRecord
{
    protected static string $resource = VisitorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->guard('school_users')->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return VisitorResource::getUrl();
    }

    protected function afterCreate(): void
    {
        $this->redirect(VisitorResource::getUrl(), navigate: true);
    }
}