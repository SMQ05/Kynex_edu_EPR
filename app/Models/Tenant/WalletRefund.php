<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A wallet refund request. Approval debits the student's wallet.
 */
class WalletRefund extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'student_id',
        'amount_paisas',
        'method',
        'reason',
        'status',
        'note',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
            'reviewed_at'   => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'reviewed_by');
    }

    /** Approve and debit the student's wallet (throws if insufficient balance). */
    public function approve(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        Wallet::forStudent($this->student_id)
            ->debit((int) $this->amount_paisas, 'refund', null, $this->reason);

        $this->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->guard('school_users')->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function reject(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->guard('school_users')->id(),
            'reviewed_at' => now(),
        ]);
    }
}
