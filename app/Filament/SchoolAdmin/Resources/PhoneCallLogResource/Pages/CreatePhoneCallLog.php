<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PhoneCallLogResource\Pages;

use App\Filament\SchoolAdmin\Resources\PhoneCallLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhoneCallLog extends CreateRecord
{
    protected static string $resource = PhoneCallLogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
