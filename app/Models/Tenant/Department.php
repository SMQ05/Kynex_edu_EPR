<?php

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'head_id',
        'description',
    ];

    /* ── Relations ─────────────────────────── */

    public function head(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'head_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }
}
