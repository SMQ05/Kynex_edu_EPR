<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CheckPushDelivery;
use App\Models\DeviceToken;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\NotificationLog;
use App\Models\Tenant\NotificationTemplate;
use App\Models\Tenant\Student;
use App\Services\Push\Drivers\FcmDriver;
use App\Services\Sms\SmsServiceFactory;
use App\Services\WhatsApp\WhatsAppServiceFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * NotificationService — Unified notification dispatcher with push-first priority chain.
 *
 * Priority order:
 *   1. Push (FCM) — if user has active device tokens
 *   2. WhatsApp — fallback after 24h if push not confirmed delivered
 *   3. SMS — fallback if WhatsApp fails or disabled
 *   4. Email — fallback if SMS fails or disabled
 *
 * If a user has NO active device tokens, push is skipped and the
 * fallback chain (WhatsApp -> SMS -> Email) fires immediately.
 *
 * Channel enforcement: checks tenant allow_* flags before sending.
 */
class NotificationService
{
    /**
     * Send a notification using a template slug.
     */
    public function sendFromTemplate(
        string $templateSlug,
        Model $notifiable,
        array $data = [],
        ?string $channelOverride = null,
        ?string $eventTrigger = null,
    ): ?NotificationLog {
        $template = NotificationTemplate::where('slug', $templateSlug)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            Log::warning("NotificationService: Template '{$templateSlug}' not found or inactive.");
            return null;
        }

        $channel = $channelOverride ?? $template->channel;
        $body    = $template->render($data);
        $subject = $template->renderSubject($data);

        return $this->dispatch($channel, $notifiable, $body, $subject, $template->id, $eventTrigger, $data);
    }

    /**
     * Send a raw notification without a template.
     */
    public function sendRaw(
        string $channel,
        Model $notifiable,
        string $body,
        ?string $subject = null,
        ?string $eventTrigger = null,
        array $variables = [],
    ): NotificationLog {
        return $this->dispatch($channel, $notifiable, $body, $subject, null, $eventTrigger, $variables);
    }

    /**
     * Send an immediate in-app notification (static helper).
     */
    public static function sendImmediate(
        string $channel,
        string $userId,
        string $body,
        ?string $eventTrigger = null,
        array $variables = [],
    ): void {
        if ($channel === 'in_app') {
            $deepLink = $eventTrigger
                ? DeepLinkService::generate($eventTrigger, $variables)
                : null;

            InAppNotification::create([
                'user_id'    => $userId,
                'title'      => 'Notification',
                'body'       => $body,
                'type'       => 'info',
                'action_url' => $deepLink,
            ]);
        }
    }

    /**
     * Send fallback notification for a push that was not delivered.
     *
     * Called by CheckPushDelivery job after 24h if push_delivered_at is still null.
     * Follows the chain: WhatsApp -> SMS -> Email.
     */
    public function sendFallback(InAppNotification $notification): void
    {
        $user = $notification->user;

        if (! $user) {
            Log::warning('NotificationService::sendFallback: User not found', [
                'notification_id' => $notification->id,
            ]);
            return;
        }

        $fallbackChannel = null;

        // Try WhatsApp first
        if (! $this->isChannelBlocked('whatsapp') && ($user->whatsapp ?? $user->phone)) {
            try {
                $this->sendWhatsApp($user, $notification->body);
                $fallbackChannel = 'whatsapp';
            } catch (\Throwable $e) {
                Log::warning('NotificationService: WhatsApp fallback failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Try SMS if WhatsApp failed or unavailable
        if (! $fallbackChannel && ! $this->isChannelBlocked('sms') && $user->phone) {
            try {
                $this->sendSms($user, $notification->body);
                $fallbackChannel = 'sms';
            } catch (\Throwable $e) {
                Log::warning('NotificationService: SMS fallback failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Try Email if SMS failed or unavailable
        if (! $fallbackChannel && ! $this->isChannelBlocked('email') && $user->email) {
            try {
                $this->sendEmail($user, $notification->title ?? 'Notification', $notification->body);
                $fallbackChannel = 'email';
            } catch (\Throwable $e) {
                Log::warning('NotificationService: Email fallback failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update the notification with fallback info
        $notification->update([
            'fallback_sent_at' => $fallbackChannel ? now() : null,
            'fallback_channel' => $fallbackChannel,
        ]);

        if ($fallbackChannel) {
            Log::info("NotificationService: Fallback sent via {$fallbackChannel}", [
                'notification_id' => $notification->id,
            ]);
        } else {
            Log::warning('NotificationService: All fallback channels failed', [
                'notification_id' => $notification->id,
            ]);
        }
    }

    /**
     * Dispatch the notification via the appropriate channel.
     *
     * Push-first priority chain:
     *   - If channel is 'database' or 'push': attempt push first, schedule
     *     24h fallback check via CheckPushDelivery.
     *   - If user has no device tokens: skip push, send via WhatsApp/SMS/Email immediately.
     *   - Direct channel sends (sms, whatsapp, email) bypass the priority chain.
     */
    protected function dispatch(
        string $channel,
        Model $notifiable,
        string $body,
        ?string $subject = null,
        ?string $templateId = null,
        ?string $eventTrigger = null,
        array $variables = [],
    ): NotificationLog {
        // Enforce tenant channel permissions
        if ($this->isChannelBlocked($channel)) {
            Log::info("NotificationService: Channel '{$channel}' is disabled by tenant settings. Skipping.");

            return NotificationLog::create([
                'template_id'     => $templateId,
                'channel'         => $channel,
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id'   => $notifiable->getKey(),
                'subject'         => $subject,
                'body'            => $body,
                'status'          => 'blocked',
                'error_message'   => 'Channel disabled by tenant notification settings.',
            ]);
        }

        // Generate deep link if event trigger is provided
        $deepLink = null;
        if ($eventTrigger) {
            $deepLink = DeepLinkService::generate($eventTrigger, $variables);
        }

        // For SMS/WhatsApp, append deep link to message body
        if ($deepLink && in_array($channel, ['sms', 'whatsapp'])) {
            $body .= "\nView details: {$deepLink}";
        }

        $log = NotificationLog::create([
            'template_id'     => $templateId,
            'channel'         => $channel,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id'   => $notifiable->getKey(),
            'subject'         => $subject,
            'body'            => $body,
            'status'          => 'pending',
        ]);

        try {
            // For database/in_app channels: use push-first priority chain
            if (in_array($channel, ['database', 'in_app'])) {
                $this->dispatchWithPushPriority($notifiable, $subject, $body, $deepLink, $log);
            } else {
                // Direct channel sends bypass the priority chain
                match ($channel) {
                    'sms'      => $this->sendSms($notifiable, $body),
                    'email'    => $this->sendEmail($notifiable, $subject ?? 'Notification', $body),
                    'whatsapp' => $this->sendWhatsApp($notifiable, $body),
                    'push'     => $this->sendPush($notifiable, $subject ?? 'Notification', $body, $deepLink),
                    default    => throw new \InvalidArgumentException("Unknown channel: {$channel}"),
                };

                $log->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("NotificationService: Failed to send via {$channel}: {$e->getMessage()}");
        }

        return $log;
    }

    /**
     * Push-first priority chain for database/in_app notifications.
     *
     * 1. Always create InAppNotification record.
     * 2. If user has active device tokens and push is allowed:
     *    -> Send via FCM, schedule 24h fallback check.
     * 3. If user has NO device tokens:
     *    -> Skip push, send via WhatsApp -> SMS -> Email immediately.
     */
    protected function dispatchWithPushPriority(
        Model $notifiable,
        ?string $subject,
        string $body,
        ?string $actionUrl,
        NotificationLog $log,
    ): void {
        // 1. Always create the in-app notification record
        $inApp = InAppNotification::create([
            'user_id'    => $notifiable->getKey(),
            'title'      => $subject ?? 'Notification',
            'body'       => $body,
            'type'       => 'info',
            'action_url' => $actionUrl,
        ]);

        $userId = $notifiable->getKey();
        $hasPushTokens = DeviceToken::where('school_user_id', $userId)
            ->active()
            ->exists();

        $pushAllowed = ! $this->isChannelBlocked('in_app');

        // 2. If user has active device tokens and push is allowed -> send push
        if ($hasPushTokens && $pushAllowed) {
            $result = $this->sendPush(
                $notifiable,
                $subject ?? 'Notification',
                $body,
                $actionUrl,
            );

            if ($result['sent'] > 0) {
                $inApp->update(['push_sent_at' => now()]);

                $log->update([
                    'status'  => 'sent',
                    'channel' => 'push',
                    'sent_at' => now(),
                ]);

                // Schedule 24h fallback check
                CheckPushDelivery::dispatch($inApp->id)
                    ->delay(now()->addHours(24));

                return;
            }
        }

        // 3. No push tokens or push failed -> immediate fallback chain
        $log->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        $this->sendImmediateFallback($notifiable, $body, $subject, $inApp);
    }

    /**
     * Immediate fallback chain when push is not available.
     * WhatsApp -> SMS -> Email (first success stops the chain).
     */
    protected function sendImmediateFallback(
        Model $notifiable,
        string $body,
        ?string $subject,
        InAppNotification $inApp,
    ): void {
        $fallbackChannel = null;

        // Try WhatsApp
        if (! $this->isChannelBlocked('whatsapp') && ($notifiable->whatsapp ?? $notifiable->phone ?? null)) {
            try {
                $this->sendWhatsApp($notifiable, $body);
                $fallbackChannel = 'whatsapp';
            } catch (\Throwable) {
                // Continue to next channel
            }
        }

        // Try SMS
        if (! $fallbackChannel && ! $this->isChannelBlocked('sms') && ($notifiable->phone ?? null)) {
            try {
                $this->sendSms($notifiable, $body);
                $fallbackChannel = 'sms';
            } catch (\Throwable) {
                // Continue to next channel
            }
        }

        // Try Email
        if (! $fallbackChannel && ! $this->isChannelBlocked('email') && ($notifiable->email ?? null)) {
            try {
                $this->sendEmail($notifiable, $subject ?? 'Notification', $body);
                $fallbackChannel = 'email';
            } catch (\Throwable) {
                // All fallbacks failed
            }
        }

        if ($fallbackChannel) {
            $inApp->update([
                'fallback_sent_at' => now(),
                'fallback_channel' => $fallbackChannel,
            ]);
        }
    }

    /**
     * Send push notification via FCM driver.
     *
     * @return array{sent: int, failed: int, delivered_tokens: array<string>}
     */
    protected function sendPush(
        Model $notifiable,
        string $title,
        string $body,
        ?string $actionUrl = null,
    ): array {
        $data = [];
        if ($actionUrl) {
            $data['action_url'] = $actionUrl;
        }

        $fcm = app(FcmDriver::class);

        return $fcm->send(
            schoolUserId: $notifiable->getKey(),
            title: $title,
            body: $body,
            data: $data,
        );
    }

    /**
     * Check if a notification channel is disabled by tenant settings.
     */
    protected function isChannelBlocked(string $channel): bool
    {
        $tenant = tenant();

        if (! $tenant) {
            return false;
        }

        return match ($channel) {
            'database', 'in_app', 'push' => ! (bool) ($tenant->allow_app_notifications ?? true),
            'whatsapp'                    => ! (bool) ($tenant->allow_whatsapp ?? true),
            'sms'                         => ! (bool) ($tenant->allow_sms ?? true),
            'email'                       => ! (bool) ($tenant->allow_email ?? true),
            default                       => false,
        };
    }

    protected function sendDatabase(Model $notifiable, ?string $subject, string $body, ?string $actionUrl = null): void
    {
        if (method_exists($notifiable, 'getKey')) {
            InAppNotification::create([
                'user_id'    => $notifiable->getKey(),
                'title'      => $subject ?? 'Notification',
                'body'       => $body,
                'type'       => 'info',
                'action_url' => $actionUrl,
            ]);
        }

        if (method_exists($notifiable, 'notify')) {
            $notifiable->notify(new \Illuminate\Notifications\Messages\DatabaseMessage([
                'subject'    => $subject,
                'body'       => $body,
                'action_url' => $actionUrl,
            ]));
        }
    }

    protected function sendSms(Model $notifiable, string $body): void
    {
        $phone = $notifiable->phone ?? null;

        if (! $phone) {
            throw new \RuntimeException('Notifiable has no phone number.');
        }

        $tenant = tenant();
        $sms    = $tenant ? SmsServiceFactory::make($tenant) : null;

        if (! $sms) {
            throw new \RuntimeException('No SMS driver configured for this tenant.');
        }

        $sms->send($phone, $body);
    }

    protected function sendEmail(Model $notifiable, string $subject, string $body): void
    {
        $email = $notifiable->email ?? null;

        if (! $email) {
            throw new \RuntimeException('Notifiable has no email address.');
        }

        Mail::raw($body, function ($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
        });
    }

    protected function sendWhatsApp(Model $notifiable, string $body): void
    {
        $whatsapp = $notifiable->whatsapp ?? $notifiable->phone ?? null;

        if (! $whatsapp) {
            throw new \RuntimeException('Notifiable has no WhatsApp number.');
        }

        $tenant = tenant();
        $wa     = $tenant ? WhatsAppServiceFactory::make($tenant) : null;

        if (! $wa) {
            throw new \RuntimeException('No WhatsApp driver configured for this tenant.');
        }

        $wa->sendText($whatsapp, $body);
    }

    // ── Student-direct notification helper ─────────────────────────

    /**
     * Send a notification directly to a student via WhatsApp/SMS if they have a phone number.
     * Also creates an in-app notification if the student has a portal account.
     */
    public function sendToStudentDirectly(
        Student $student,
        string $title,
        string $body,
        ?string $eventTrigger = null,
    ): void {
        $tenant = tenant();
        if (! $tenant) {
            return;
        }

        // In-app notification for student portal
        if ($student->school_user_id) {
            InAppNotification::create([
                'user_id'  => $student->school_user_id,
                'title'    => $title,
                'body'     => $body,
                'type'     => $eventTrigger ?? 'general',
            ]);
        }

        $phone = $student->phone;
        if (! $phone) {
            return;
        }

        // Try WhatsApp first, then SMS
        if ($tenant->allow_whatsapp) {
            try {
                $wa = WhatsAppServiceFactory::make($tenant);
                $wa?->sendText($phone, $body);
                return;
            } catch (\Throwable $e) {
                Log::debug("Student WhatsApp failed, falling back to SMS: {$e->getMessage()}");
            }
        }

        if ($tenant->allow_sms) {
            try {
                $sms = SmsServiceFactory::make($tenant);
                $sms?->send($phone, $body);
            } catch (\Throwable $e) {
                Log::debug("Student SMS also failed: {$e->getMessage()}");
            }
        }
    }
}
