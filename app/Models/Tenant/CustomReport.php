<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomReport extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'base_model',
        'selected_columns',
        'filters',
        'sort_by',
        'sort_direction',
        'group_by',
        'is_scheduled',
        'schedule_frequency',
        'schedule_email',
        'last_run_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'selected_columns'  => 'array',
            'filters'           => 'array',
            'is_scheduled'      => 'boolean',
            'last_run_at'       => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->scheduled()->where('schedule_frequency', $frequency);
    }
}
