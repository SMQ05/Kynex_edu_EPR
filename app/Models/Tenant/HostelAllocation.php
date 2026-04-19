<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostelAllocation extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'student_id',
        'room_id',
        'bed_number',
        'allocated_by',
        'check_in_date',
        'check_out_date',
        'expected_check_out',
        'status',
        'monthly_fee_paisas',
        'notes',
    ];

    protected array $paisaFields = ['monthly_fee_paisas'];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'expected_check_out' => 'date',
            'bed_number' => 'integer',
            'monthly_fee_paisas' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'allocated_by');
    }

    protected static function booted(): void
    {
        static::created(function (self $allocation) {
            $allocation->room?->increment('occupied_beds');
            if ($allocation->room && $allocation->room->occupied_beds >= $allocation->room->total_beds) {
                $allocation->room->update(['status' => 'full']);
            }
        });
    }
}
