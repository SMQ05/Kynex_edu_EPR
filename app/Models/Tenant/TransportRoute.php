<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    use HasUlids, HasPaisaAttributes;

    protected string $primaryPaisaColumn = 'fare_paisas';

    protected $fillable = [
        'name',
        'description',
        'vehicle_id',
        'fare_paisas',
        'departure_time',
        'arrival_time',
        'distance_km',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fare_paisas'    => 'integer',
            'departure_time' => 'datetime:H:i',
            'arrival_time'   => 'datetime:H:i',
            'distance_km'    => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TransportStop::class, 'route_id')->orderBy('stop_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TransportAssignment::class, 'route_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getStudentCountAttribute(): int
    {
        return $this->assignments()->where('is_active', true)->count();
    }
}
