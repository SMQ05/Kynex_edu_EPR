<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\DownloadContentResource\Pages;

use App\Filament\SchoolAdmin\Resources\DownloadContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDownloadContent extends CreateRecord
{
    protected static string $resource = DownloadContentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
