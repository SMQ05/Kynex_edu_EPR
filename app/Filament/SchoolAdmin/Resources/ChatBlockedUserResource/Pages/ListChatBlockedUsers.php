<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChatBlockedUsers extends ListRecords
{
    protected static string $resource = ChatBlockedUserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Block a user')];
    }
}
