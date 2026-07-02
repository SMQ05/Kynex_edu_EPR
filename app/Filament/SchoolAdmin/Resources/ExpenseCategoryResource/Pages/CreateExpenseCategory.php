<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource\Pages;
use App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource;
use Filament\Resources\Pages\CreateRecord;
class CreateExpenseCategory extends CreateRecord
{
    protected static string $resource = ExpenseCategoryResource::class;
        protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
}
