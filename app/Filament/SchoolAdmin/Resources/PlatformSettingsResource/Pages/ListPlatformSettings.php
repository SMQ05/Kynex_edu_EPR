<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PlatformSettingsResource\Pages;

use App\Filament\SchoolAdmin\Resources\PlatformSettingsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformSettings extends ListRecords
{
    protected static string $resource = PlatformSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
