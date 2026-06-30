<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\InventorySaleResource\Pages;

use App\Filament\SchoolAdmin\Resources\InventorySaleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventorySale extends EditRecord
{
    protected static string $resource = InventorySaleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * Only metadata is editable post-sale. Item/quantity/price are locked
     * (stock was already adjusted via the paired transaction), so we drop
     * the display-only PKR field and never touch the stored paisas/total.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['unit_price_pkr'], $data['total_pkr'], $data['unit_price_paisas'], $data['total_paisas'], $data['quantity'], $data['item_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
  
}
