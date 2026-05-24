<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChatBlockedUser extends EditRecord
{
    protected static string $resource = ChatBlockedUserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Unblock')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
