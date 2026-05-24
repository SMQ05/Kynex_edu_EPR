<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\AdmissionEnquiry;
use App\Models\Tenant\PhoneCallLog;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily in-app reminders for front-office follow-ups that are due today or
 * overdue: admission enquiries and phone calls flagged for follow-up.
 * Notifies the assigned staff member (when set). Runs in tenant context
 * via the scheduler. Automation hook for Phase 1.
 */
class FrontOfficeFollowUpReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function handle(NotificationService $notifications): void
    {
        // ── Admission enquiries due for follow-up ──
        AdmissionEnquiry::query()
            ->whereNull('deleted_at')
            ->dueForFollowUp()
            ->whereNotNull('assigned_to')
            ->get(['id', 'name', 'assigned_to', 'next_follow_up_date'])
            ->each(function (AdmissionEnquiry $enquiry) use ($notifications): void {
                $this->notify(
                    $notifications,
                    (string) $enquiry->assigned_to,
                    "Admission enquiry follow-up due: {$enquiry->name} (due " . optional($enquiry->next_follow_up_date)->format('d M') . ').',
                    'enquiry.follow_up_due',
                );
            });

        // ── Phone calls due for follow-up ──
        PhoneCallLog::query()
            ->whereNull('deleted_at')
            ->dueForFollowUp()
            ->whereNotNull('created_by')
            ->get(['id', 'name', 'created_by', 'follow_up_date'])
            ->each(function (PhoneCallLog $call) use ($notifications): void {
                $this->notify(
                    $notifications,
                    (string) $call->created_by,
                    "Phone call follow-up due: {$call->name} (due " . optional($call->follow_up_date)->format('d M') . ').',
                    'call.follow_up_due',
                );
            });
    }

    private function notify(NotificationService $notifications, string $userId, string $body, string $trigger): void
    {
        try {
            $notifications::sendImmediate('in_app', $userId, $body, $trigger);
        } catch (\Throwable) {
            // best effort — never let one failure abort the batch
        }
    }
}
