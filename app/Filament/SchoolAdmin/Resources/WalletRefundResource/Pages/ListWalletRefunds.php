<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletRefundResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletRefundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWalletRefunds extends ListRecords
{
    protected static string $resource = WalletRefundResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
