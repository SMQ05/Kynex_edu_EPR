<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryTransactionResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditInventoryTransaction extends EditRecord
{
    protected static string $resource = InventoryTransactionResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
