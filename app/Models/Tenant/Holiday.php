<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    /** Number of calendar days the holiday spans (inclusive). */
   public function getDaysAttribute(): int
{
    if (! $this->start_date || ! $this->end_date) {
        return 0;
    }

    return (int) $this->start_date->diffInDays($this->end_date) + 1;
}
}
