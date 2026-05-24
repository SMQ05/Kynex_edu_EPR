<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PostalDispatchResource\Pages;

use App\Filament\SchoolAdmin\Resources\PostalDispatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostalDispatch extends CreateRecord
{
    protected static string $resource = PostalDispatchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
