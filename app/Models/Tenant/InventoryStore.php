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

class InventoryStore extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus;

    protected $fillable = [
        'name',
        'location',
        'manager_id',
        'campus_id',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'manager_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'store_id');
    }
}
