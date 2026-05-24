<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PostalReceiveResource\Pages;

use App\Filament\SchoolAdmin\Resources\PostalReceiveResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostalReceive extends CreateRecord
{
    protected static string $resource = PostalReceiveResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
