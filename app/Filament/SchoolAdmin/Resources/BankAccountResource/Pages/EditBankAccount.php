<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BankAccountResource\Pages;

use App\Filament\SchoolAdmin\Resources\BankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
