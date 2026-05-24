<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An inventory sale to a student/staff/other. Creating a sale also writes
 * a paired negative inventory_transaction ("sell"), which decrements stock
 * via InventoryTransaction's booted() hook — keeping stock consistent with
 * the existing receive/issue flow.
 */
class InventorySale extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, TracksCreator;

    public const BUYER_TYPES = [
        'student' => 'Student',
        'staff'   => 'Staff',
        'other'   => 'Other',
    ];

    /** @var list<string> */
    protected array $paisaFields = ['unit_price_paisas', 'total_paisas'];

    protected $fillable = [
        'item_id',
        'quantity',
        'unit_price_paisas',
        'total_paisas',
        'buyer_type',
        'student_id',
        'staff_user_id',
        'buyer_name',
        'sold_on',
        'reference',
        'notes',
        'transaction_id',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'unit_price_paisas' => 'integer',
            'total_paisas'      => 'integer',
            'sold_on'           => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'staff_user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'transaction_id');
    }

    // ── Computed ───────────────────────────────────────────────────

    public function getBuyerLabelAttribute(): string
    {
        return match ($this->buyer_type) {
            'student' => $this->student
                ? trim(($this->student->first_name ?? '') . ' ' . ($this->student->last_name ?? ''))
                : ($this->buyer_name ?? '—'),
            'staff'   => $this->staff?->name ?? ($this->buyer_name ?? '—'),
            default   => $this->buyer_name ?? '—',
        };
    }
}
