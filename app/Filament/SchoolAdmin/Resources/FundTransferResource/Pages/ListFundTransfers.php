<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FundTransferResource\Pages;

use App\Filament\SchoolAdmin\Resources\FundTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFundTransfers extends ListRecords
{
    protected static string $resource = FundTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
