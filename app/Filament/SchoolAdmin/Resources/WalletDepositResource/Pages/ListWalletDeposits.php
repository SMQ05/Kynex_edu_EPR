<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletDepositResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletDepositResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWalletDeposits extends ListRecords
{
    protected static string $resource = WalletDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
