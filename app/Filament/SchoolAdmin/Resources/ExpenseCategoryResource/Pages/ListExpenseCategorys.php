<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource\Pages;
use App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListExpenseCategorys extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
