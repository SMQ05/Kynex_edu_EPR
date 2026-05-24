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
 * A wallet top-up request. Approval credits the student's wallet.
 */
class WalletDeposit extends Model
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
        'reference',
        'slip_path',
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

    /** Approve and credit the student's wallet. */
    public function approve(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        Wallet::forStudent($this->student_id)
            ->credit((int) $this->amount_paisas, 'deposit', $this->reference, $this->note);

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
