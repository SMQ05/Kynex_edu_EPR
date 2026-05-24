<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource\Pages;

use App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankPaymentRequest extends EditRecord
{
    protected static string $resource = BankPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
