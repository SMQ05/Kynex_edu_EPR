<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ChatInvitationResource\Pages;

use App\Filament\SchoolAdmin\Resources\ChatInvitationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChatInvitation extends EditRecord
{
    protected static string $resource = ChatInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
