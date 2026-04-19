<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostelRoom extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'building_id',
        'room_type_id',
        'room_number',
        'floor_number',
        'total_beds',
        'occupied_beds',
        'status',
        'facilities',
        'monthly_fee_paisas',
    ];

    protected array $paisaFields = ['monthly_fee_paisas'];

    protected function casts(): array
    {
        return [
            'floor_number' => 'integer',
            'total_beds' => 'integer',
            'occupied_beds' => 'integer',
            'facilities' => 'array',
            'monthly_fee_paisas' => 'integer',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(HostelBuilding::class, 'building_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(HostelRoomType::class, 'room_type_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class, 'room_id');
    }

    public function activeAllocations(): HasMany
    {
        return $this->allocations()->where('status', 'active');
    }

    public function getEffectiveFeePaisasAttribute(): int
    {
        return $this->monthly_fee_paisas ?? $this->roomType?->monthly_fee_paisas ?? 0;
    }

    public function getAvailableBedsAttribute(): int
    {
        return max(0, $this->total_beds - $this->occupied_beds);
    }
}
