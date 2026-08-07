<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryCategoryResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditInventoryCategory extends EditRecord
{
    protected static string $resource = InventoryCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
}
