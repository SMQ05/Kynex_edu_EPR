<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'is_main_campus',
        'campus_owner_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main_campus' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'campus_owner_user_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'campus_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'campus_id');
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class, 'campus_id');
    }

    public function schoolUsers(): HasMany
    {
        return $this->hasMany(SchoolUser::class, 'campus_id');
    }
}
