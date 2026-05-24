<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatBlockedUser extends CreateRecord
{
    protected static string $resource = ChatBlockedUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
