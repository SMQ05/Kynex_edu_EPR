<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsContactMessageResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsContactMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsContactMessage extends EditRecord
{
    protected static string $resource = CmsContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /** Mark a 'new' message as 'read' when opened. */
    protected function afterFill(): void
    {
        if ($this->record->status === 'new') {
            $this->record->update(['status' => 'read']);
        }
    }
    
}
