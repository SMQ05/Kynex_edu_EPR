<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Physical classroom / lab / hall.
 */
class Classroom extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const TYPES = [
        'classroom' => 'Classroom',
        'lab'       => 'Laboratory',
        'hall'      => 'Hall',
        'library'   => 'Library',
        'other'     => 'Other',
    ];

    protected $fillable = [
        'name',
        'code',
        'room_type',
        'capacity',
        'building',
        'floor',
        'is_active',
        'note',
        'campus_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'capacity'  => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
