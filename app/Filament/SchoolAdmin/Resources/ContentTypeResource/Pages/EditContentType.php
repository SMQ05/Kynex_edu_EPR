<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ContentTypeResource\Pages;

use App\Filament\SchoolAdmin\Resources\ContentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentType extends EditRecord
{
    protected static string $resource = ContentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
