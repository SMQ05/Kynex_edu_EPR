<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasUlids, HasPaisaAttributes;

    protected array $paisaFields = ['unit_price_paisas'];

    protected $fillable = [
        'item_id',
        'transaction_type',
        'quantity',
        'unit_price_paisas',
        'reference_number',
        'supplier_id',
        'issued_to_user_id',
        'issued_to_department',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_paisas' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $txn) {
            $txn->item?->increment('current_quantity', $txn->quantity);
        });
    }

    /* ── Relations ─────────────────────────── */

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'supplier_id');
    }

    public function issuedToUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'issued_to_user_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'recorded_by');
    }
}
