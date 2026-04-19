<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostelBuilding extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus;

    protected $fillable = [
        'name',
        'type',
        'address',
        'total_floors',
        'warden_id',
        'campus_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_floors' => 'integer',
        ];
    }

    public function warden(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'warden_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class, 'building_id');
    }
}
