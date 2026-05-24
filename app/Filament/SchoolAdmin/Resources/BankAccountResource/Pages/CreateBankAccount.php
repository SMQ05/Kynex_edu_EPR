<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BankAccountResource\Pages;

use App\Filament\SchoolAdmin\Resources\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
