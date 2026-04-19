<?php

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CafeteriaTransaction extends Model
{
    use HasUlids, HasPaisaAttributes;

    protected $fillable = [
        'student_id',
        'school_user_id',
        'menu_item_id',
        'campus_id',
        'served_by',
        'transaction_type',
        'quantity',
        'unit_price_paisas',
        'total_paisas',
        'payment_method',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'           => 'integer',
            'unit_price_paisas'  => 'integer',
            'total_paisas'       => 'integer',
            'transaction_date'   => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(CafeteriaMenuItem::class, 'menu_item_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'served_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->where('transaction_date', $date);
    }

    public function scopePurchases($query)
    {
        return $query->where('transaction_type', 'purchase');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getTotalInPkrAttribute(): string
    {
        return self::formatPkr($this->total_paisas ?? 0);
    }

    public function getUnitPriceInPkrAttribute(): string
    {
        return self::formatPkr($this->unit_price_paisas ?? 0);
    }
}
