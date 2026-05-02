<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryStoreResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryStoreResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInventoryStore extends CreateRecord
{
    protected static string $resource = InventoryStoreResource::class;
}
