<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use App\Services\FeesService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A bank-transfer fee payment submitted for approval. Approval records a
 * real FeePayment (allocated to the student's outstanding fees) via the
 * shared FeesService, keeping receipts/ledgers consistent.
 */
class BankPaymentRequest extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, TracksCreator;

    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    /** @var list<string> */
    protected array $paisaFields = ['amount_paisas'];

    protected $fillable = [
        'student_id',
        'amount_paisas',
        'bank_reference',
        'paid_on',
        'slip_path',
        'status',
        'note',
        'receipt_number',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
            'paid_on'       => 'date',
            'reviewed_at'   => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'reviewed_by');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'requested_by');
    }

    // ── Workflow ───────────────────────────────────────────────────

    /**
     * Approve: record a FeePayment against the student's outstanding fees
     * (oldest-due first), then mark this request approved.
     */
    public function approve(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $allocations = $this->allocateToOutstanding((int) $this->amount_paisas);

        if ($allocations === []) {
            throw new \RuntimeException('Student has no outstanding fees to apply this payment to.');
        }

        $reviewer = auth()->guard('school_users')->id();

        $payment = app(FeesService::class)->collectPayment(
            studentId:      $this->student_id,
            feeAllocations: $allocations,
            paymentMethod:  'bank',
            collectedBy:    $reviewer ?? '00000000000000000000000000',
            transactionRef: $this->bank_reference,
            remarks:        'Bank payment approved' . ($this->note ? ' — ' . $this->note : ''),
        );

        $this->update([
            'status'         => 'approved',
            'receipt_number' => $payment->receipt_number,
            'reviewed_by'    => $reviewer,
            'reviewed_at'    => now(),
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

    /**
     * Split an amount across the student's outstanding StudentFee rows,
     * oldest-due first, up to each fee's net-due balance.
     *
     * @return array<string,int>  student_fee_id => amount_paisas
     */
    protected function allocateToOutstanding(int $amountPaisas): array
    {
        $remaining = $amountPaisas;
        $allocations = [];

        $fees = StudentFee::query()
            ->where('student_id', $this->student_id)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('due_date')
            ->get();

        foreach ($fees as $fee) {
            if ($remaining <= 0) {
                break;
            }

            $netDue = (int) $fee->amount_paisas
                + (int) $fee->fine_paisas
                - (int) $fee->discount_paisas
                - (int) $fee->paid_paisas;

            if ($netDue <= 0) {
                continue;
            }

            $apply = min($remaining, $netDue);
            $allocations[$fee->id] = $apply;
            $remaining -= $apply;
        }

        return $allocations;
    }
}
