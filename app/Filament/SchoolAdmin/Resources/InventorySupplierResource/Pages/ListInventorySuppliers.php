<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventorySupplierResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventorySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListInventorySuppliers extends ListRecords
{
    protected static string $resource = InventorySupplierResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
  
}
