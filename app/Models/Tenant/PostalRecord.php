<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Postal register row — `direction` = receive | dispatch. Backs both the
 * Postal Receive and Postal Dispatch Filament resources.
 */
class PostalRecord extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const DIRECTION_RECEIVE = 'receive';
    public const DIRECTION_DISPATCH = 'dispatch';

    protected $fillable = [
        'direction',
        'reference_no',
        'from_party',
        'to_party',
        'title',
        'details',
        'postal_type_id',
        'record_date',
        'attachment_path',
        'is_confidential',
        'note',
        'campus_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'record_date'     => 'date',
            'is_confidential' => 'boolean',
        ];
    }

    public function postalType(): BelongsTo
    {
        return $this->belongsTo(FrontOfficeReference::class, 'postal_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function scopeReceive($query)
    {
        return $query->where('direction', self::DIRECTION_RECEIVE);
    }

    public function scopeDispatch($query)
    {
        return $query->where('direction', self::DIRECTION_DISPATCH);
    }
}
