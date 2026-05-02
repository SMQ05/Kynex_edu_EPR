<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\BudgetResource\Pages;
use App\Filament\SchoolAdmin\Resources\BudgetResource;
use Filament\Resources\Pages\CreateRecord;
class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;
}
