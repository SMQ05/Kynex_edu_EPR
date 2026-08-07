<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\InventoryTransactionResource\Pages;
use App\Filament\SchoolAdmin\Resources\InventoryTransactionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInventoryTransaction extends CreateRecord
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth('school_users')->user() ?? auth()->user();

        if (! $user) {
            abort(403, 'You must be signed in as a school user to record stock movements.');
        }

        $data['recorded_by'] = $user->id;

        // Issues and write-offs are stored as negative quantities so the
        // booted() hook on InventoryTransaction decrements stock correctly.
        if (in_array($data['transaction_type'] ?? '', ['issue', 'write_off'], true)) {
            $data['quantity'] = -abs((int) ($data['quantity'] ?? 0));
        }

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
  
}
