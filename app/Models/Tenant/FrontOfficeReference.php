<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared reference/lookup data for the Front Office module, keyed by
 * `type` (complaint_type, source, reference, postal_type, visit_purpose,
 * call_purpose).
 */
class FrontOfficeReference extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const TYPES = [
        'complaint_type' => 'Complaint Type',
        'source'         => 'Enquiry Source',
        'reference'      => 'Reference',
        'postal_type'    => 'Postal Type',
        'visit_purpose'  => 'Visit Purpose',
        'call_purpose'   => 'Call Purpose',
    ];

    protected $fillable = [
        'type',
        'name',
        'color',
        'description',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @return array<string,string> id => name, for Select options. */
    public static function options(string $type): array
    {
        return static::query()
            ->ofType($type)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
