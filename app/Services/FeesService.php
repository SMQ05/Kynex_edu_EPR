<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\FeePaymentItem;
use App\Models\Tenant\FeeMaster;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FeesService — Fee-related business logic including generation,
 * collection, discounts, and refund initiation.
 *
 * All money amounts are in paisas (integer).
 * All refund requests are routed through the ApprovalService.
 */
class FeesService
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    // ══════════════════════════════════════════════════════════════
    //  Fee Generation
    // ══════════════════════════════════════════════════════════════

    /**
     * Generate student fees for a class/section based on FeeMaster records.
     * Protected by advisory lock to prevent double-billing from concurrent workers.
     *
     * Creates StudentFee records for each student × fee type combination
     * for the given academic year and month.
     *
     * @return array{generated: int, skipped: int}
     * @throws \RuntimeException If fee generation is already running for this class/month.
     */
    public function generateStudentFees(
        string $classId,
        ?string $sectionId,
        string $academicYearId,
        string $month, // format: YYYY-MM
    ): array {
        $lockKey = crc32("fees:{$classId}:{$month}");

        $acquired = DB::selectOne(
            'SELECT pg_try_advisory_lock(?) AS acquired',
            [$lockKey]
        )->acquired;

        if (! $acquired) {
            throw new \RuntimeException(
                "Fee generation for this class/month is already running. Please wait."
            );
        }

        try {
            return $this->doGenerateStudentFees($classId, $sectionId, $academicYearId, $month);
        } finally {
            DB::statement('SELECT pg_advisory_unlock(?)', [$lockKey]);
        }
    }

    /**
     * Actual fee generation logic (called inside advisory lock).
     *
     * @return array{generated: int, skipped: int}
     */
    private function doGenerateStudentFees(
        string $classId,
        ?string $sectionId,
        string $academicYearId,
        string $month,
    ): array {
        $feeMasters = FeeMaster::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get();

        $studentsQuery = Student::where('class_id', $classId)
            ->where('status', 'enrolled');

        if ($sectionId) {
            $studentsQuery->where('section_id', $sectionId);
        }

        $students = $studentsQuery->get();

        $generated = 0;
        $skipped = 0;

        foreach ($students as $student) {
            foreach ($feeMasters as $feeMaster) {
                $exists = StudentFee::where('student_id', $student->id)
                    ->where('fee_type_id', $feeMaster->fee_type_id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('month', $month)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                StudentFee::create([
                    'student_id'       => $student->id,
                    'fee_type_id'      => $feeMaster->fee_type_id,
                    'academic_year_id' => $academicYearId,
                    'month'            => $month,
                    'amount_paisas'    => $feeMaster->amount_paisas,
                    'discount_paisas'  => 0,
                    'fine_paisas'      => 0,
                    'paid_paisas'      => 0,
                    'status'           => 'unpaid',
                    'due_date'         => Carbon::parse($month . '-01')->endOfMonth(),
                ]);

                $generated++;
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    // ══════════════════════════════════════════════════════════════
    //  Fee Collection / Payment
    // ══════════════════════════════════════════════════════════════

    /**
     * Collect a payment from a student for one or more fees.
     *
     * @param  array<string, int>  $feeAllocations  [student_fee_id => amount_paisas]
     * @return FeePayment
     */
    public function collectPayment(
        string $studentId,
        array $feeAllocations,
        string $paymentMethod,
        string $collectedBy,
        ?string $transactionRef = null,
        ?string $remarks = null,
    ): FeePayment {
        return DB::transaction(function () use (
            $studentId, $feeAllocations, $paymentMethod, $collectedBy, $transactionRef, $remarks,
        ) {
            $totalPaisas = array_sum($feeAllocations);

            $payment = FeePayment::create([
                'student_id'      => $studentId,
                'receipt_number'  => $this->generateReceiptNumber(),
                'amount_paisas'   => $totalPaisas,
                'payment_method'  => $paymentMethod,
                'transaction_ref' => $transactionRef,
                'collected_by'    => $collectedBy,
                'remarks'         => $remarks,
                'paid_at'         => now(),
            ]);

            foreach ($feeAllocations as $studentFeeId => $amountPaisas) {
                FeePaymentItem::create([
                    'fee_payment_id' => $payment->id,
                    'student_fee_id' => $studentFeeId,
                    'amount_paisas'  => $amountPaisas,
                ]);

                $studentFee = StudentFee::find($studentFeeId);
                if ($studentFee) {
                    $studentFee->increment('paid_paisas', $amountPaisas);

                    if ($studentFee->balance_paisas <= 0) {
                        $studentFee->update(['status' => 'paid']);
                    } else {
                        $studentFee->update(['status' => 'partial']);
                    }
                }
            }

            return $payment;
        });
    }

    /**
     * Generate a unique receipt number.
     */
    protected function generateReceiptNumber(): string
    {
        $prefix = 'RCT';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }

    // ══════════════════════════════════════════════════════════════
    //  Discounts
    // ══════════════════════════════════════════════════════════════

    /**
     * Apply a discount to a student fee.
     */
    public function applyDiscount(
        string $studentFeeId,
        int $discountPaisas,
        string $reason,
        string $approvedBy,
    ): StudentFee {
        $fee = StudentFee::findOrFail($studentFeeId);

        $fee->update([
            'discount_paisas' => $fee->discount_paisas + $discountPaisas,
        ]);

        // Re-evaluate status after discount
        $fee->refresh();
        if ($fee->balance_paisas <= 0 && $fee->status !== 'paid') {
            $fee->update(['status' => 'paid']);
        }

        return $fee;
    }

    /**
     * Apply a fine (late fee) to a student fee.
     */
    public function applyFine(
        string $studentFeeId,
        int $finePaisas,
        ?string $reason = null,
    ): StudentFee {
        $fee = StudentFee::findOrFail($studentFeeId);

        $fee->update([
            'fine_paisas' => $fee->fine_paisas + $finePaisas,
        ]);

        // If it was paid, it's now unpaid/partial again
        $fee->refresh();
        if ($fee->balance_paisas > 0 && $fee->status === 'paid') {
            $fee->update(['status' => 'partial']);
        }

        return $fee;
    }

    // ══════════════════════════════════════════════════════════════
    //  Auto Late-Fee Application
    // ══════════════════════════════════════════════════════════════

    /**
     * Apply late fees to all overdue unpaid/partial fees.
     *
     * @return int Number of fees that had fines applied
     */
    public function applyLateFees(int $finePerDayPaisas, ?int $maxFinePaisas = null): int
    {
        $overdueFees = StudentFee::overdue()
            ->whereIn('status', ['unpaid', 'partial'])
            ->get();

        $count = 0;

        foreach ($overdueFees as $fee) {
            $daysLate = Carbon::parse($fee->due_date)->diffInDays(now());
            $calculatedFine = $daysLate * $finePerDayPaisas;

            if ($maxFinePaisas !== null) {
                $calculatedFine = min($calculatedFine, $maxFinePaisas);
            }

            // Only apply if fine would increase (don't reduce existing fines)
            if ($calculatedFine > $fee->fine_paisas) {
                $fee->update(['fine_paisas' => $calculatedFine]);
                $count++;
            }
        }

        return $count;
    }

    // ══════════════════════════════════════════════════════════════
    //  Queries / Reports
    // ══════════════════════════════════════════════════════════════

    /**
     * Get cumulative payment timeline for a student.
     * Uses PostgreSQL SUM() OVER window function for running totals.
     *
     * @return array<int, object{payment_date: string, total_amount_paisas: int, cumulative_paid_paisas: int}>
     */
    public function getStudentFeeTimeline(string $studentId): array
    {
        return DB::select("
            SELECT
                payment_date,
                total_amount_paisas,
                SUM(total_amount_paisas) OVER (
                    PARTITION BY student_id
                    ORDER BY payment_date
                    ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                ) AS cumulative_paid_paisas
            FROM fee_payments
            WHERE student_id = ?
            ORDER BY payment_date
        ", [$studentId]);
    }

    /**
     * Get fee summary for a student.
     *
     * @return array{total_fees: int, total_paid: int, total_discount: int, total_fine: int, balance: int, fee_count: int}
     */
    public function getStudentFeeSummary(string $studentId, ?string $academicYearId = null): array
    {
        $query = StudentFee::where('student_id', $studentId);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $fees = $query->get();

        return [
            'total_fees'     => $fees->sum('amount_paisas'),
            'total_paid'     => $fees->sum('paid_paisas'),
            'total_discount' => $fees->sum('discount_paisas'),
            'total_fine'     => $fees->sum('fine_paisas'),
            'balance'        => $fees->sum(fn ($f) => $f->balance_paisas),
            'fee_count'      => $fees->count(),
        ];
    }

    /**
     * Get class fee collection summary.
     *
     * @return array{total_students: int, total_fees: int, total_collected: int, total_pending: int, collection_rate: float}
     */
    public function getClassFeeCollectionSummary(
        string $classId,
        ?string $sectionId,
        string $academicYearId,
    ): array {
        $query = StudentFee::where('academic_year_id', $academicYearId)
            ->whereHas('student', function ($q) use ($classId, $sectionId) {
                $q->where('class_id', $classId);
                if ($sectionId) {
                    $q->where('section_id', $sectionId);
                }
            });

        $fees = $query->get();

        $totalFees = $fees->sum(fn ($f) => $f->net_payable_paisas);
        $totalCollected = $fees->sum('paid_paisas');

        return [
            'total_students'  => $fees->pluck('student_id')->unique()->count(),
            'total_fees'      => $totalFees,
            'total_collected'  => $totalCollected,
            'total_pending'    => $totalFees - $totalCollected,
            'collection_rate'  => $totalFees > 0
                ? round(($totalCollected / $totalFees) * 100, 2)
                : 0,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  Refund (Approval-based — existing logic preserved)
    // ══════════════════════════════════════════════════════════════

    /**
     * Initiate a refund request for a student fee.
     *
     * Always routes through ApprovalService — never executes directly.
     * Returns the ApprovalRequest so the caller can show a confirmation message.
     */
    public function initiateRefund(
        string $studentFeeId,
        int $refundPaisas,
        string $reason,
        string $requestedBy,
    ): ApprovalRequest {
        $fee = StudentFee::findOrFail($studentFeeId);

        /** @var Model $requester */
        $requester = SchoolUser::findOrFail($requestedBy);

        return $this->approvalService->submit(
            requestedBy: $requester,
            actionType: 'fee_refund',
            subject: $fee,
            payload: [
                'amount_paisas' => $refundPaisas,
                'reason'        => $reason,
            ],
        );
    }

    // ══════════════════════════════════════════════════════════════
    //  Helpers
    // ══════════════════════════════════════════════════════════════

    /**
     * Format a paisa amount as PKR for display.
     */
    public static function formatPkr(int $paisas): string
    {
        return 'PKR ' . number_format($paisas / 100, 2);
    }
}
