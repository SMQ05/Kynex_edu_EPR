<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PlatformSettingsResource\Pages;

use App\Filament\SchoolAdmin\Resources\PlatformSettingsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformSettings extends EditRecord
{
    protected static string $resource = PlatformSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
