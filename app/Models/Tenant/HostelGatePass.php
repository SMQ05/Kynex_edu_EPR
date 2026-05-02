<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelGatePass extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'purpose',
        'out_date_time',
        'expected_return_date_time',
        'actual_return_date_time',
        'approved_by',
        'status',
        'parent_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'out_date_time' => 'datetime',
            'expected_return_date_time' => 'datetime',
            'actual_return_date_time' => 'datetime',
            'parent_notified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'approved_by');
    }
}
