<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource\Pages;

use App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankPaymentRequests extends ListRecords
{
    protected static string $resource = BankPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
