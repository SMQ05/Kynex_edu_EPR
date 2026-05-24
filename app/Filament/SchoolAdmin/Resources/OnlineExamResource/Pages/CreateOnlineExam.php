<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\OnlineExamResource\Pages;

use App\Filament\SchoolAdmin\Resources\OnlineExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOnlineExam extends CreateRecord
{
    protected static string $resource = OnlineExamResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
