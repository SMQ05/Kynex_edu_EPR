<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected array $paisaFields = ['unit_price_paisas'];

    protected $fillable = [
        'name',
        'code',
        'category_id',
        'store_id',
        'unit',
        'current_quantity',
        'minimum_quantity',
        'unit_price_paisas',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'integer',
            'minimum_quantity' => 'integer',
            'unit_price_paisas' => 'integer',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InventoryStore::class, 'store_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }

    /* ── Scopes ────────────────────────────── */

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_quantity', '<=', 'minimum_quantity');
    }

    /* ── Helpers ───────────────────────────── */

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_quantity <= $this->minimum_quantity;
    }
}
