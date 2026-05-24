<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource\Pages;

use App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankPaymentRequest extends CreateRecord
{
    protected static string $resource = BankPaymentRequestResource::class;

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
