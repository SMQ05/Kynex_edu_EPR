<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\StudentFee;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendFeeReminderWithFallback — Sends fee reminders with a 24-hour fallback channel.
 *
 * Strategy (Part 3b):
 *  1. Try primary channel (WhatsApp) immediately.
 *  2. If the initial channel fails, dispatch a second attempt via SMS
 *     after a 24-hour delay.
 *
 * This job handles both the initial send and (when $isFallback = true) the
 * fallback SMS send.
 */
class SendFeeReminderWithFallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    /**
     * @param  string  $studentFeeId    The StudentFee ULID
     * @param  string  $primaryChannel  'whatsapp' | 'sms' | 'email'
     * @param  bool    $isFallback      True when this is the 24h fallback attempt
     */
    public function __construct(
        public string $studentFeeId,
        public string $primaryChannel = 'whatsapp',
        public bool   $isFallback = false,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $studentFee = StudentFee::with(['student.guardians'])->find($this->studentFeeId);

        if (! $studentFee) {
            Log::warning("SendFeeReminderWithFallback: StudentFee {$this->studentFeeId} not found.");
            return;
        }

        $student = $studentFee->student;
        if (! $student) {
            return;
        }

        // Get primary guardian
        $guardian = $student->guardians()
            ->where('is_primary_contact', true)
            ->first()
            ?? $student->guardians()->first();

        if (! $guardian) {
            Log::info("SendFeeReminderWithFallback: No guardian found for student {$student->id}");
            return;
        }

        $channel = $this->isFallback ? 'sms' : $this->primaryChannel;

        $data = [
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'amount_due'   => number_format($studentFee->amount / 100, 2),
            'due_date'     => $studentFee->due_date?->format('M d, Y') ?? 'N/A',
            'school_name'  => config('app.name', 'School'),
        ];

        try {
            $log = $notificationService->sendFromTemplate(
                templateSlug: 'fee.reminder',
                notifiable:   $guardian,
                data:         $data,
                channelOverride: $channel,
                eventTrigger: 'fee.due',
            );

            // If primary channel succeeded, no fallback needed
            if ($log && $log->status === 'sent') {
                Log::info("SendFeeReminderWithFallback: Sent via {$channel} for fee {$this->studentFeeId}");
                return;
            }

            // Primary failed → dispatch fallback after 24 hours (only if not already fallback)
            if (! $this->isFallback) {
                self::dispatch(
                    studentFeeId:   $this->studentFeeId,
                    primaryChannel: $this->primaryChannel,
                    isFallback:     true,
                )->delay(now()->addHours(24));

                Log::info("SendFeeReminderWithFallback: Primary failed, fallback SMS scheduled in 24h for fee {$this->studentFeeId}");
            }

        } catch (\Throwable $e) {
            Log::error("SendFeeReminderWithFallback: Error for fee {$this->studentFeeId}: {$e->getMessage()}");

            if (! $this->isFallback) {
                self::dispatch(
                    studentFeeId:   $this->studentFeeId,
                    primaryChannel: $this->primaryChannel,
                    isFallback:     true,
                )->delay(now()->addHours(24));
            }
        }
    }
}
