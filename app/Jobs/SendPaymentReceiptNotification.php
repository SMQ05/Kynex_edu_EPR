<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\FeePayment;
use App\Models\Tenant\InAppNotification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendPaymentReceiptNotification — Sends WhatsApp + SMS + in-app notification
 * to the guardian after a successful online fee payment.
 */
class SendPaymentReceiptNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected string $feePaymentId,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $feePayment = FeePayment::with([
            'student.guardians',
            'student.schoolClass',
        ])->find($this->feePaymentId);

        if (! $feePayment) {
            Log::warning('SendPaymentReceiptNotification: FeePayment not found', [
                'fee_payment_id' => $this->feePaymentId,
            ]);
            return;
        }

        $student      = $feePayment->student;
        $studentName  = $student ? ($student->first_name . ' ' . $student->last_name) : 'Student';
        $amountPkr    = number_format($feePayment->total_amount_paisas / 100, 2);
        $receiptNo    = $feePayment->receipt_number;
        $schoolName   = tenancy()->tenant?->school_name ?? 'School';

        $message = "Payment confirmed! PKR {$amountPkr} received for {$studentName}. Receipt: {$receiptNo}. Thank you. — {$schoolName}";

        // Get primary guardian
        $guardian = $student?->guardians()
            ->where('is_primary_contact', true)
            ->first();

        if (! $guardian) {
            $guardian = $student?->guardians()->first();
        }

        if ($guardian) {
            // Send WhatsApp notification
            try {
                $notificationService->sendRaw(
                    'whatsapp',
                    $guardian,
                    $message,
                    null,
                    'payment.confirmed',
                    [
                        'amount'         => $amountPkr,
                        'student_name'   => $studentName,
                        'receipt_number' => $receiptNo,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('SendPaymentReceiptNotification: WhatsApp failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Send SMS notification
            try {
                $notificationService->sendRaw(
                    'sms',
                    $guardian,
                    $message,
                    null,
                    'payment.confirmed',
                    [
                        'amount'         => $amountPkr,
                        'student_name'   => $studentName,
                        'receipt_number' => $receiptNo,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('SendPaymentReceiptNotification: SMS failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create in-app notification for parent portal user
        if ($guardian && $guardian->school_user_id) {
            InAppNotification::create([
                'user_id'    => $guardian->school_user_id,
                'title'      => 'Payment Confirmed',
                'body'       => $message,
                'type'       => 'success',
                'action_url' => '/admin/fee-collection',
            ]);
        }

        // Also notify student's school_user if exists
        if ($student && $student->school_user_id) {
            InAppNotification::create([
                'user_id'    => $student->school_user_id,
                'title'      => 'Fee Payment Confirmed',
                'body'       => "Your fee payment of PKR {$amountPkr} has been received. Receipt: {$receiptNo}",
                'type'       => 'success',
                'action_url' => '/admin/fee-collection',
            ]);
        }

        Log::info('SendPaymentReceiptNotification: Notifications sent', [
            'fee_payment_id' => $this->feePaymentId,
            'student'        => $studentName,
        ]);
    }
}
