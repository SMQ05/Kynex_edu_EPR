<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletResource;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
