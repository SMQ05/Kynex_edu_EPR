<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\DownloadContentResource\Pages;

use App\Filament\SchoolAdmin\Resources\DownloadContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDownloadContent extends EditRecord
{
    protected static string $resource = DownloadContentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
