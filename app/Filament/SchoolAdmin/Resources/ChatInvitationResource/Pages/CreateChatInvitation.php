<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ChatInvitationResource\Pages;

use App\Filament\SchoolAdmin\Resources\ChatInvitationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatInvitation extends CreateRecord
{
    protected static string $resource = ChatInvitationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
