<?php

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CafeteriaMenuItem extends Model
{
    use HasUlids, HasPaisaAttributes;

    protected $fillable = [
        'campus_id',
        'name',
        'category',
        'description',
        'price_paisas',
        'image_path',
        'is_available',
        'is_vegetarian',
        'allergens',
        'calories',
        'preparation_time_minutes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_paisas'              => 'integer',
            'is_available'              => 'boolean',
            'is_vegetarian'             => 'boolean',
            'calories'                  => 'integer',
            'preparation_time_minutes'  => 'integer',
            'sort_order'                => 'integer',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CafeteriaTransaction::class, 'menu_item_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getPriceInPkrAttribute(): string
    {
        return self::formatPkr($this->price_paisas ?? 0);
    }
}
