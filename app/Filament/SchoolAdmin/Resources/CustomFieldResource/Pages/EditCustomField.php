<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CustomFieldResource\Pages;

use App\Filament\SchoolAdmin\Resources\CustomFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomField extends EditRecord
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
