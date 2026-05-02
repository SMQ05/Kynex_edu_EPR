<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\BudgetResource\Pages;

use App\Filament\SchoolAdmin\Resources\BudgetResource;
use App\Filament\SchoolAdmin\Resources\BudgetResource\Widgets\BudgetOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBudgets extends ListRecords
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeaderWidgets(): array
    {
        return [
            BudgetOverviewWidget::class,
        ];
    }
}
