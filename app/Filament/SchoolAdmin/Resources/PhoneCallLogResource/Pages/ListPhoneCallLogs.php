<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PhoneCallLogResource\Pages;

use App\Filament\SchoolAdmin\Resources\PhoneCallLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhoneCallLogs extends ListRecords
{
    protected static string $resource = PhoneCallLogResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
