<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AccountHeadResource\Pages;

use App\Filament\SchoolAdmin\Resources\AccountHeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountHead extends CreateRecord
{
    protected static string $resource = AccountHeadResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
