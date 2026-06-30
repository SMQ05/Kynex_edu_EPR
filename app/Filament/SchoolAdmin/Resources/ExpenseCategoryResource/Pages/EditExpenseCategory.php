<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource\Pages;
use App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExpenseCategory extends EditRecord
{
    protected static string $resource = ExpenseCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
}
