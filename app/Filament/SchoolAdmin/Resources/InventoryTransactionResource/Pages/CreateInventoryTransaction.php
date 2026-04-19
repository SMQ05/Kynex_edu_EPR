<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryTransactionResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryTransactionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInventoryTransaction extends CreateRecord
{
    protected static string $resource = InventoryTransactionResource::class;
}
