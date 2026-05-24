<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletDepositResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletDepositResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWalletDeposit extends EditRecord
{
    protected static string $resource = WalletDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
