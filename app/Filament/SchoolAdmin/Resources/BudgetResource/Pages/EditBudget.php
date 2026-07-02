<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\BudgetResource\Pages;
use App\Filament\SchoolAdmin\Resources\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
