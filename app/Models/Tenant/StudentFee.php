<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\StudentFeeStatus;
use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFee extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    /** @var list<string> */
    protected array $paisaFields = ['amount_paisas', 'discount_paisas', 'fine_paisas', 'paid_paisas'];

    protected $fillable = [
        'student_id',
        'fee_type_id',
        'academic_year_id',
        'due_date',
        'amount_paisas',
        'discount_paisas',
        'fine_paisas',
        'paid_paisas',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_paisas' => 'integer',
            'discount_paisas' => 'integer',
            'fine_paisas' => 'integer',
            'paid_paisas' => 'integer',
            'status' => StudentFeeStatus::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(FeePaymentItem::class);
    }

    // ── Computed ───────────────────────────────────────────────────

    /**
     * Net payable = amount + fine - discount.
     */
    public function getNetPayablePaisasAttribute(): int
    {
        return $this->amount_paisas + $this->fine_paisas - $this->discount_paisas;
    }

    /**
     * Balance remaining = net payable - paid.
     */
    public function getBalancePaisasAttribute(): int
    {
        return $this->net_payable_paisas - $this->paid_paisas;
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOverdue($query)
    {
        return $query->where('status', StudentFeeStatus::Pending)
            ->where('due_date', '<', now());
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [StudentFeeStatus::Pending, StudentFeeStatus::Partial]);
    }
}
