<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PhoneCallLogResource\Pages;

use App\Filament\SchoolAdmin\Resources\PhoneCallLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhoneCallLog extends EditRecord
{
    protected static string $resource = PhoneCallLogResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
