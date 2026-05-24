<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BankAccountResource\Pages;

use App\Filament\SchoolAdmin\Resources\BankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
