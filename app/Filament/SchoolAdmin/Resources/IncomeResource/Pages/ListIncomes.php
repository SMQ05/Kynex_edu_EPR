<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\IncomeResource\Pages;

use App\Filament\SchoolAdmin\Resources\IncomeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
