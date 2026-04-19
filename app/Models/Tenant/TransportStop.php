<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportStop extends Model
{
    use HasUlids;

    protected $fillable = [
        'route_id',
        'name',
        'stop_order',
        'pickup_time',
        'drop_time',
        'latitude',
        'longitude',
        'landmark',
    ];

    protected function casts(): array
    {
        return [
            'stop_order'  => 'integer',
            'pickup_time' => 'datetime:H:i',
            'drop_time'   => 'datetime:H:i',
            'latitude'    => 'decimal:7',
            'longitude'   => 'decimal:7',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }
}
