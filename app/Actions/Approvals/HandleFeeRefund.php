<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\StudentFee;
use App\Services\FeesService;
use Illuminate\Support\Str;

/**
 * HandleFeeRefund — Executed when a fee_refund approval is approved.
 *
 * 1. Marks the StudentFee as refunded.
 * 2. Creates a negative FeePayment as audit trail.
 * 3. Notifies the requester via in-app notification.
 */
class HandleFeeRefund
{
    public function handle(ApprovalRequest $approval): void
    {
        $fee = StudentFee::findOrFail($approval->subject_id);
        $student = $fee->student;

        // 1. Mark fee as refunded
        $fee->update(['status' => 'refunded']);

        // 2. Create negative fee_payment record as audit trail
        FeePayment::create([
            'student_id'          => $fee->student_id,
            'receipt_number'      => 'REF-' . Str::upper(Str::random(8)),
            'total_amount_paisas' => -$approval->payload['amount_paisas'],
            'payment_date'        => now(),
            'payment_method'      => 'refund',
            'notes'               => $approval->payload['reason'],
            'collected_by'        => $approval->reviewed_by_id,
        ]);

        // 3. Notify requester via in_app notification
        $amountFormatted = FeesService::formatPkr($approval->payload['amount_paisas']);
        $studentName = $student ? $student->full_name : 'Unknown';

        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Refund Approved',
            'body'    => "Refund of {$amountFormatted} for student {$studentName} has been approved.",
            'type'    => 'success',
        ]);
    }
}
