<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Casts\AsEnum;
use App\Enums\ExpenseApprovalStatus;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\RequiresApproval;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, RequiresApproval;

    /** @var list<string> */
    protected array $paisaFields = ['amount_paisas'];

    protected $fillable = [
        'category_id',
        'budget_id',
        'title',
        'description',
        'amount_paisas',
        'expense_date',
        'payment_method',
        'reference_number',
        'receipt_path',
        'recorded_by',
        'approved_by',
        'approval_status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
            'expense_date' => 'date',
            'payment_method' => AsEnum::of(PaymentMethod::class),
            'approval_status' => ExpenseApprovalStatus::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'approved_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('approval_status', ExpenseApprovalStatus::Approved);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', ExpenseApprovalStatus::Pending);
    }
}
