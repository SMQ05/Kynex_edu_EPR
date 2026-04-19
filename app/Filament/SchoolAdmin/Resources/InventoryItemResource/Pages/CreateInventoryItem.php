<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryItemResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryItemResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;
}
