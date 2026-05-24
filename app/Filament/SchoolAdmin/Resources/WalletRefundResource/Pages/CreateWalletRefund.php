<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletRefundResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletRefundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWalletRefund extends CreateRecord
{
    protected static string $resource = WalletRefundResource::class;

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
