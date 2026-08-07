<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryItemResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditInventoryItem extends EditRecord
{
    protected static string $resource = InventoryItemResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
  
}
