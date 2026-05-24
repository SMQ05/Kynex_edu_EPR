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

class Event extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const AUDIENCES = [
        'all'      => 'Everyone',
        'students' => 'Students',
        'parents'  => 'Parents',
        'staff'    => 'Staff',
        'teachers' => 'Teachers',
    ];

    protected $fillable = [
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'location',
        'audience',
        'color',
        'is_published',
        'campus_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at'     => 'datetime',
            'end_at'       => 'datetime',
            'all_day'      => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
