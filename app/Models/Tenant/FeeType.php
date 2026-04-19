<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeType extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'fee_group_id',
        'name',
        'is_recurring',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_recurring' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function feeGroup(): BelongsTo
    {
        return $this->belongsTo(FeeGroup::class);
    }

    public function feeMasters(): HasMany
    {
        return $this->hasMany(FeeMaster::class);
    }

    public function studentFees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }
}
