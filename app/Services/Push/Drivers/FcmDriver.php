<?php

declare(strict_types=1);

namespace App\Services\Push\Drivers;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FcmDriver — Sends push notifications via Firebase Cloud Messaging (legacy HTTP API).
 *
 * Uses the legacy FCM endpoint (https://fcm.googleapis.com/fcm/send) which
 * authenticates with a static server key. The HTTP v1 API requires OAuth 2.0
 * token exchange from a service account, which adds complexity. When upgrading
 * to HTTP v1, replace this driver entirely.
 *
 * Uses Laravel's HTTP client. No artificial delay — FCM is an official API.
 * Invalid tokens (InvalidRegistration / NotRegistered responses) are automatically deactivated.
 */
class FcmDriver
{
    private string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key', '');
    }

    /**
     * Send a push notification to all active device tokens for a user.
     *
     * @return array{sent: int, failed: int, delivered_tokens: array<string>}
     */
    public function send(
        string $schoolUserId,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null,
    ): array {
        $tokens = DeviceToken::where('school_user_id', $schoolUserId)
            ->active()
            ->get();

        if ($tokens->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'delivered_tokens' => []];
        }

        $sent = 0;
        $failed = 0;
        $deliveredTokens = [];

        foreach ($tokens as $deviceToken) {
            $success = $this->sendToToken(
                $deviceToken,
                $title,
                $body,
                $data,
                $imageUrl,
            );

            if ($success) {
                $sent++;
                $deliveredTokens[] = $deviceToken->token;
                $deviceToken->update(['last_used_at' => now()]);
            } else {
                $failed++;
            }
        }

        return [
            'sent'             => $sent,
            'failed'           => $failed,
            'delivered_tokens' => $deliveredTokens,
        ];
    }

    /**
     * Send a push notification to a single device token.
     */
    private function sendToToken(
        DeviceToken $deviceToken,
        string $title,
        string $body,
        array $data,
        ?string $imageUrl,
    ): bool {
        // Legacy FCM HTTP API — authenticates with static server key.
        // RISK 3 (FCM auth): The legacy API (fcm.googleapis.com/fcm/send) is
        // deprecated but still functional. The HTTP v1 API requires OAuth 2.0
        // service account credentials and a different payload structure.
        // This legacy endpoint is intentionally used here because HTTP v1 with
        // a static server key in the Authorization header would return 401.
        // Plan migration to HTTP v1 + service account JSON before Google
        // sunsets the legacy endpoint (monitor Firebase deprecation notices).
        $url = 'https://fcm.googleapis.com/fcm/send';

        $notification = [
            'title' => $title,
            'body'  => $body,
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
        }

        // FCM data values must all be strings
        $stringData = array_map('strval', $data);

        $payload = [
            'to'           => $deviceToken->token,
            'notification' => $notification,
            'data'         => $stringData,
            'priority'     => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$this->serverKey}",
                'Content-Type'  => 'application/json',
            ])
                ->timeout(15)
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();

                // Legacy API returns success/failure counts per token
                if (($result['failure'] ?? 0) > 0) {
                    $error = $result['results'][0]['error'] ?? '';

                    // InvalidRegistration or NotRegistered = token is stale
                    if (in_array($error, ['InvalidRegistration', 'NotRegistered'])) {
                        $deviceToken->update(['is_active' => false]);

                        Log::info('FcmDriver: Token deactivated (invalid/expired)', [
                            'token_id' => $deviceToken->id,
                            'error'    => $error,
                        ]);
                    }

                    return false;
                }

                return ($result['success'] ?? 0) > 0;
            }

            Log::warning('FcmDriver: FCM API error', [
                'status'   => $response->status(),
                'body'     => $response->json(),
                'token_id' => $deviceToken->id,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('FcmDriver: Exception sending push', [
                'message'  => $e->getMessage(),
                'token_id' => $deviceToken->id,
            ]);

            return false;
        }
    }
}
