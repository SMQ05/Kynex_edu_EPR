<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryCategoryResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListInventoryCategorys extends ListRecords
{
    protected static string $resource = InventoryCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
