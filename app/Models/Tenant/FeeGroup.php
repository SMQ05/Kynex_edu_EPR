<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeGroup extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function feeTypes(): HasMany
    {
        return $this->hasMany(FeeType::class);
    }
}
