<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ChatInvitationResource\Pages;

use App\Filament\SchoolAdmin\Resources\ChatInvitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChatInvitations extends ListRecords
{
    protected static string $resource = ChatInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
