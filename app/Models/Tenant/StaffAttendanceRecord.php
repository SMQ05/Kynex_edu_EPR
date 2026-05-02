<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\StaffAttendanceStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceRecord extends Model
{
    use HasUlids;

    protected $fillable = [
        'school_user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'marked_by',
        'overtime_minutes',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'status'           => StaffAttendanceStatus::class,
            'overtime_minutes' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'marked_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }
}
