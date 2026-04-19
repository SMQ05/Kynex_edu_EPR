<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasUlids;

    protected $fillable = [
        'vehicle_number',
        'vehicle_type',
        'make',
        'model',
        'year',
        'seating_capacity',
        'fuel_type',
        'driver_name',
        'driver_phone',
        'driver_license',
        'gps_device_id',
        'insurance_number',
        'insurance_expiry',
        'fitness_expiry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'seating_capacity'  => 'integer',
            'insurance_expiry'  => 'date',
            'fitness_expiry'    => 'date',
            'is_active'         => 'boolean',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
