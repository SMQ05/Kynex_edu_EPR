<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryCategoryResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryCategoryResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInventoryCategory extends CreateRecord
{
    protected static string $resource = InventoryCategoryResource::class;
}
