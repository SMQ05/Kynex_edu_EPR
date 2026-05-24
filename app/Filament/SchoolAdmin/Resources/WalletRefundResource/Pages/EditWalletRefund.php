<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletRefundResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletRefundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWalletRefund extends EditRecord
{
    protected static string $resource = WalletRefundResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
