<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletDepositResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletDepositResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWalletDeposit extends CreateRecord
{
    protected static string $resource = WalletDepositResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->guard('school_users')->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
