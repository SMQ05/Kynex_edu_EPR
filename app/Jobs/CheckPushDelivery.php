<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\InAppNotification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CheckPushDelivery — Checks if a push notification was "delivered" (read).
 *
 * Dispatched 24 hours after a push notification is sent. If the user has
 * NOT read the notification by then (read_at is still null), this job
 * triggers the fallback chain: WhatsApp -> SMS -> Email.
 *
 * LIMITATION: True FCM delivery receipts require Data Messages + client-side
 * ACK. For now, we treat "read_at IS NOT NULL" as delivery confirmation
 * (the user opened the app and viewed the notification). This is a pragmatic
 * approximation — a user who received the push but didn't tap it will still
 * trigger the fallback after 24 hours.
 */
class CheckPushDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $inAppNotificationId,
    ) {}

    public function handle(): void
    {
        $notification = InAppNotification::find($this->inAppNotificationId);

        if (! $notification) {
            Log::warning('CheckPushDelivery: Notification not found', [
                'id' => $this->inAppNotificationId,
            ]);
            return;
        }

        // If read_at is set, treat as "delivered" — no fallback needed.
        // RISK 5 (delivery proxy): read_at is NOT a true FCM delivery receipt.
        // A user who received the push but didn't open the notification will
        // still trigger the fallback after 24h. True delivery receipts require
        // Data Messages + client-side ACK (reportDelivered). This is an accepted
        // approximation for Phase 11 — the worst case is a duplicate reminder
        // via WhatsApp/SMS, which is acceptable for educational notifications.
        if ($notification->read_at !== null) {
            $notification->update(['push_delivered_at' => $notification->read_at]);
            return;
        }

        // Push was not confirmed as delivered — trigger fallback chain
        Log::info('CheckPushDelivery: Push not delivered, triggering fallback', [
            'notification_id' => $notification->id,
            'user_id'         => $notification->user_id,
        ]);

        app(NotificationService::class)->sendFallback($notification);
    }
}
