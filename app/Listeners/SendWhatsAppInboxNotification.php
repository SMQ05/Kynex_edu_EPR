<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;
use App\Services\Push\Drivers\FcmDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notification + in-app bell alert to all staff who have
 * the 'view_whatsapp_inbox' permission when a new parent WhatsApp message arrives.
 *
 * Runs async so it doesn't block the webhook response.
 */
class SendWhatsAppInboxNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(WhatsAppMessageReceived $event): void
    {
        $conversation = $event->conversation;
        $message      = $event->message;

        $contactDisplay = $conversation->contact_name
            ?: $conversation->contact_phone;

        $title = 'New WhatsApp Message';
        $body  = "{$contactDisplay}: " . mb_substr($message->body, 0, 60)
                 . (mb_strlen($message->body) > 60 ? '…' : '');

        // Get all active school users with inbox permission
        $staffUsers = SchoolUser::query()
            ->where('is_active', true)
            ->permission('view_whatsapp_inbox')
            ->get();

        if ($staffUsers->isEmpty()) {
            return;
        }

        $fcm = app(FcmDriver::class);

        foreach ($staffUsers as $user) {
            // 1. Create in-app notification (shows in bell)
            try {
                InAppNotification::create([
                    'user_id'    => $user->id,
                    'title'      => $title,
                    'body'       => $body,
                    'type'       => 'info',
                    'action_url' => '/admin/whatsapp-inbox?conversation=' . $conversation->id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp inbox: failed to create in-app notification', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            // 2. Send FCM push if user has active tokens (FcmDriver fetches tokens by user ID)
            try {
                $fcm->send($user->id, $title, $body, [
                    'type'            => 'whatsapp_inbox',
                    'conversation_id' => $conversation->id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp inbox: FCM push failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
