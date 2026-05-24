<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\IncomeResource\Pages;

use App\Filament\SchoolAdmin\Resources\IncomeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncome extends EditRecord
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
