<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\IncomeResource\Pages;

use App\Filament\SchoolAdmin\Resources\IncomeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncome extends CreateRecord
{
    protected static string $resource = IncomeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
