<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInstallmentPlan extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'plan_name',
        'total_amount_paisas',
        'total_installments',
        'status',
        'created_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'total_amount_paisas' => 'integer',
            'total_installments'  => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FeeInstallmentItem::class, 'installment_plan_id');
    }

    // ── Computed ────────────────────────────────────────────────────

    public function getTotalPaidPaisasAttribute(): int
    {
        return (int) $this->installments()->sum('paid_paisas');
    }

    public function getBalancePaisasAttribute(): int
    {
        return $this->total_amount_paisas - $this->total_paid_paisas;
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
