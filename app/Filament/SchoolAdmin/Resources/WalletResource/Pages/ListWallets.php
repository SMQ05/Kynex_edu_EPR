<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletResource\Pages;

use App\Filament\SchoolAdmin\Resources\WalletResource;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;
}
