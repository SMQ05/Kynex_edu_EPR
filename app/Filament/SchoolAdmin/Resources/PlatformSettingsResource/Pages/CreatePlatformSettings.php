<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PlatformSettingsResource\Pages;

use App\Filament\SchoolAdmin\Resources\PlatformSettingsResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformSettings extends CreateRecord
{
    protected static string $resource = PlatformSettingsResource::class;
}
