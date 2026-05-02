<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BookIssueResource\Pages;

use App\Filament\SchoolAdmin\Resources\BookIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBookIssue extends EditRecord
{
    protected static string $resource = BookIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
