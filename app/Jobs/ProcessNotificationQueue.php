<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessNotificationQueue — Retries failed notifications
 * from the notifications_log table.
 *
 * Part 3c: Rate limiting — unofficial gateways (evolution, jazz_sms,
 * telenor_sms, zong_sms, android_sms_gateway) require a random sleep
 * of 8–18 seconds between messages to avoid rate-limit bans.
 * Official channels (sendpk, sendpk_whatsapp, meta_official, twilio,
 * email, database/in-app) get zero delay.
 */
class ProcessNotificationQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    /**
     * Unofficial providers that require rate-limiting between sends.
     * Delay is applied PER RECIPIENT to avoid banning.
     */
    private const UNOFFICIAL_SMS_CHANNELS = [
        'android_sms_gateway',
        'jazz_sms',
        'telenor_sms',
        'zong_sms',
    ];

    private const UNOFFICIAL_WA_CHANNELS = [
        'evolution',
    ];

    public function handle(NotificationService $service): void
    {
        $pending = NotificationLog::where('status', 'pending')
            ->orWhere(function ($q) {
                $q->where('status', 'failed')
                    ->where('updated_at', '>=', now()->subHours(6));
            })
            ->limit(50)
            ->get();

        // Determine if rate limiting is needed for this tenant
        $needsRateLimit = $this->tenantNeedsRateLimit();
        $processed = 0;

        foreach ($pending as $log) {
            try {
                $notifiable = $log->notifiable;

                if (! $notifiable) {
                    $log->update([
                        'status'        => 'failed',
                        'error_message' => 'Notifiable not found.',
                    ]);
                    continue;
                }

                $service->sendRaw(
                    channel: $log->channel,
                    notifiable: $notifiable,
                    body: $log->body,
                    subject: $log->subject,
                );

                // The sendRaw already creates a new log, so update original
                $log->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);

                $processed++;

                // ── Part 3c: Rate limiting between sends ──────────────
                // RISK 2: rand(8–18s) sleep is a blunt instrument. It prevents
                // gateway bans but ties up the queue worker thread. A future
                // improvement would be to use per-provider token-bucket rate
                // limiting (e.g., Redis + ThrottlesExceptions middleware) so
                // official channels aren't blocked behind slow unofficial sends.
                // Accepted trade-off for Phase 11; revisit when scaling beyond
                // ~50 messages/batch.
                if ($needsRateLimit && in_array($log->channel, ['sms', 'whatsapp'])) {
                    $delay = rand(8, 18);
                    Log::debug("ProcessNotificationQueue: Rate limiting — sleeping {$delay}s before next send.");
                    sleep($delay);
                }

            } catch (\Throwable $e) {
                $log->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                Log::warning("ProcessNotificationQueue: Failed log {$log->id}: {$e->getMessage()}");
            }
        }

        Log::info("ProcessNotificationQueue: Processed {$processed} / {$pending->count()} pending logs.");
    }

    /**
     * Part 3c: Determine if the current tenant uses an unofficial channel
     * that requires inter-message rate limiting.
     *
     * Official channels (sendpk, meta_official, twilio) have proper rate
     * handling in their APIs. Unofficial channels (evolution WhatsApp,
     * local SMS gateways) do not — exceeding their thresholds risks bans.
     */
    private function tenantNeedsRateLimit(): bool
    {
        $tenant = tenant();

        if (! $tenant) {
            return false;
        }

        $smsChannel = $tenant->sms_channel ?? 'none';
        $waChannel  = $tenant->whatsapp_channel ?? 'none';

        return in_array($smsChannel, self::UNOFFICIAL_SMS_CHANNELS)
            || in_array($waChannel, self::UNOFFICIAL_WA_CHANNELS);
    }
}
