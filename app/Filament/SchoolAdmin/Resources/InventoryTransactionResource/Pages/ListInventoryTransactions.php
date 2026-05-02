<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryTransactionResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListInventoryTransactions extends ListRecords
{
    protected static string $resource = InventoryTransactionResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
