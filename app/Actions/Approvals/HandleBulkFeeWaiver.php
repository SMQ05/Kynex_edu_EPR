<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\StudentFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * HandleBulkFeeWaiver — Executed when a bulk_fee_waiver approval is approved.
 *
 * Phase 10.3 — Applies a discount (waiver) to StudentFee records for
 * multiple students at once. Creates a FeePayment record as audit trail.
 */
class HandleBulkFeeWaiver
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload;

        $studentIds      = $payload['student_ids'];
        $waiverPaisas    = $payload['waiver_amount_paisa'];
        $waiverReason    = $payload['waiver_reason'] ?? 'Bulk waiver';
        $feeTypeId       = $payload['fee_type_id'] ?? null;

        $processedCount = 0;

        DB::transaction(function () use ($studentIds, $waiverPaisas, $waiverReason, $feeTypeId, $approval, &$processedCount) {
            foreach ($studentIds as $studentId) {
                $query = StudentFee::where('student_id', $studentId)
                    ->whereIn('status', ['pending', 'partial']);

                if ($feeTypeId) {
                    $query->where('fee_type_id', $feeTypeId);
                }

                $fee = $query->first();

                if (! $fee) {
                    continue;
                }

                // Apply discount
                $fee->increment('discount_paisas', $waiverPaisas);

                // Recalculate status: if fully covered by discount, mark as waived
                $netPayable = $fee->amount_paisas + $fee->fine_paisas - $fee->discount_paisas;
                if ($netPayable <= $fee->paid_paisas) {
                    $fee->update(['status' => 'waived']);
                }

                // Create waiver payment record as audit trail
                FeePayment::create([
                    'student_id'          => $studentId,
                    'receipt_number'      => 'WAV-' . Str::upper(Str::random(8)),
                    'total_amount_paisas' => 0,
                    'payment_date'        => now(),
                    'payment_method'      => 'waiver',
                    'notes'               => "Bulk waiver: {$waiverReason} (PKR " . number_format($waiverPaisas / 100, 2) . ')',
                    'collected_by'        => $approval->reviewed_by_id,
                ]);

                $processedCount++;
            }
        });

        Log::info("HandleBulkFeeWaiver: Applied waiver to {$processedCount} students.", [
            'waiver_paisas' => $waiverPaisas,
            'approved_by'   => $approval->reviewed_by_id,
        ]);

        // Notify requester
        $amountFormatted = 'PKR ' . number_format($waiverPaisas / 100, 2);

        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Bulk Fee Waiver Approved',
            'body'    => "Waiver of {$amountFormatted} applied to {$processedCount} student(s).",
            'type'    => 'success',
        ]);
    }
}
