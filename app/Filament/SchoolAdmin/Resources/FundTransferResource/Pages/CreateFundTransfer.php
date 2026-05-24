<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FundTransferResource\Pages;

use App\Filament\SchoolAdmin\Resources\FundTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFundTransfer extends CreateRecord
{
    protected static string $resource = FundTransferResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
