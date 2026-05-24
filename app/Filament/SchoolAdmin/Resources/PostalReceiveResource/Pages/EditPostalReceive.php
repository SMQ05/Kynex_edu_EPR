<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PostalReceiveResource\Pages;

use App\Filament\SchoolAdmin\Resources\PostalReceiveResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostalReceive extends EditRecord
{
    protected static string $resource = PostalReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
