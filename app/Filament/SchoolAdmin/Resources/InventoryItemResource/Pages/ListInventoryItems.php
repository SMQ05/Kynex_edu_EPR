<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryItemResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListInventoryItems extends ListRecords
{
    protected static string $resource = InventoryItemResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
