<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ComplaintResource\Pages;

use App\Filament\SchoolAdmin\Resources\ComplaintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComplaint extends EditRecord
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
