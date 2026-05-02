<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasUlids, HasPaisaAttributes;

    protected string $primaryPaisaColumn = 'price_paisas';

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'publisher',
        'edition_year',
        'description',
        'rack_number',
        'total_copies',
        'available_copies',
        'price_paisas',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_copies'     => 'integer',
            'available_copies' => 'integer',
            'price_paisas'     => 'integer',
            'is_active'        => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('available_copies', '>', 0);
    }

    /**
     * Full-text search using PostgreSQL tsvector.
     * Falls back to ILIKE for very short terms (< 2 chars).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (strlen($term) < 2) {
            return $query->where('title', 'ilike', "%{$term}%");
        }

        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            [$term]
        )->orderByRaw(
            "ts_rank(search_vector, plainto_tsquery('english', ?)) DESC",
            [$term]
        );
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_copies > 0;
    }
}
