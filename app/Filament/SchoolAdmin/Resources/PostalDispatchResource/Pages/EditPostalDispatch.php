<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PostalDispatchResource\Pages;

use App\Filament\SchoolAdmin\Resources\PostalDispatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostalDispatch extends EditRecord
{
    protected static string $resource = PostalDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
