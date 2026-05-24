<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AccountHeadResource\Pages;

use App\Filament\SchoolAdmin\Resources\AccountHeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountHead extends EditRecord
{
    protected static string $resource = AccountHeadResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
