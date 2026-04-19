<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryStoreResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryStoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditInventoryStore extends EditRecord
{
    protected static string $resource = InventoryStoreResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
