<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AccountHeadResource\Pages;

use App\Filament\SchoolAdmin\Resources\AccountHeadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountHeads extends ListRecords
{
    protected static string $resource = AccountHeadResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
