<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventorySupplierResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventorySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditInventorySupplier extends EditRecord
{
    protected static string $resource = InventorySupplierResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
