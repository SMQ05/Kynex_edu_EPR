<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\InventorySaleResource\Pages;

use App\Filament\SchoolAdmin\Resources\InventorySaleResource;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InventorySale;
use App\Models\Tenant\InventoryTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInventorySale extends CreateRecord
{
    protected static string $resource = InventorySaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $unitPaisas = (int) round(((float) ($data['unit_price_pkr'] ?? 0)) * 100);
        unset($data['unit_price_pkr']);

        $qty = (int) ($data['quantity'] ?? 0);

        $item = InventoryItem::find($data['item_id'] ?? null);
        if (! $item) {
            throw ValidationException::withMessages(['item_id' => 'Item not found.']);
        }

        if ($qty < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        if ($qty > (int) $item->current_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$item->current_quantity} {$item->unit} in stock.",
            ]);
        }

        $data['unit_price_paisas'] = $unitPaisas;
        $data['total_paisas'] = $unitPaisas * $qty;

        // Clear buyer fields that don't apply to the chosen type.
        match ($data['buyer_type'] ?? 'student') {
            'student' => $data['staff_user_id'] = null,
            'staff'   => $data['student_id'] = null,
            default   => [$data['student_id'] = null, $data['staff_user_id'] = null],
        };

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): InventorySale {
            // Paired negative stock movement decrements stock via the
            // InventoryTransaction booted() hook (same path as issue/write_off).
            $txn = InventoryTransaction::create([
                'item_id'           => $data['item_id'],
                'transaction_type'  => 'sell',
                'quantity'          => -abs((int) $data['quantity']),
                'unit_price_paisas' => $data['unit_price_paisas'],
                'reference_number'  => $data['reference'] ?? null,
                'notes'             => 'Item sale' . (! empty($data['notes']) ? ' — ' . $data['notes'] : ''),
                'recorded_by'       => auth()->guard('school_users')->id(),
            ]);

            $data['transaction_id'] = $txn->id;

            return InventorySale::create($data);
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->title('Sale recorded — stock decremented')->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
