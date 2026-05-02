<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp;

use App\Events\WhatsAppMessageReceived;
use App\Jobs\ProcessWhatsAppMessage;
use App\Models\Tenant\WhatsAppConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Bot Webhook Controller
 *
 * Receives incoming WhatsApp messages from:
 *   - Meta Official Cloud API (webhook verification + message events)
 *   - Evolution API (webhook events)
 *
 * Dispatches ProcessWhatsAppMessage job for async handling.
 * The bot responds to parent queries about:
 *   1. Fee status (keyword: fee/fees/فیس)
 *   2. Attendance (keyword: attendance/حاضری)
 *   3. Results (keyword: result/results/نتائج)
 *   4. Notices (keyword: notice/notices/نوٹس)
 *   5. AI-powered free-form queries (when tenant.ai_enabled = true)
 */
class BotController extends Controller
{
    /**
     * GET /whatsapp/webhook — Meta Cloud API verification
     *
     * Meta sends a GET request with hub.mode, hub.verify_token, hub.challenge
     * to verify the webhook URL during setup.
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.whatsapp.verify_token', env('WHATSAPP_VERIFY_TOKEN', ''));

        // Per-tenant override: if the tenant stored a verify_token in their whatsapp_config, use that
        $tenantToken = tenant()?->whatsapp_config['verify_token'] ?? null;
        if ($tenantToken) {
            $verifyToken = $tenantToken;
        }

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified successfully.');
            return new Response($challenge, 200, ['Content-Type' => 'text/plain']);
        }

        Log::warning('WhatsApp webhook verification failed.', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return new Response('Forbidden', 403);
    }

    /**
     * POST /whatsapp/webhook — Incoming message handler
     *
     * Accepts webhooks from Meta Cloud API and Evolution API.
     * Extracts sender phone number and message text, then dispatches
     * the ProcessWhatsAppMessage job for async processing.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('WhatsApp webhook received', ['payload_keys' => array_keys($payload)]);

        // ── Meta Cloud API format ────────────────────────────────
        if (isset($payload['object']) && $payload['object'] === 'whatsapp_business_account') {
            return $this->handleMetaWebhook($payload);
        }

        // ── Evolution API format ─────────────────────────────────
        if (isset($payload['event'])) {
            return $this->handleEvolutionWebhook($payload);
        }

        // ── Generic / Unknown format ─────────────────────────────
        Log::info('WhatsApp webhook: Unknown format', ['payload' => $payload]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Meta Cloud API webhook payload.
     *
     * Payload structure:
     * {
     *   "object": "whatsapp_business_account",
     *   "entry": [{
     *     "changes": [{
     *       "value": {
     *         "messages": [{
     *           "from": "923001234567",
     *           "type": "text",
     *           "text": { "body": "fee status" }
     *         }]
     *       }
     *     }]
     *   }]
     * }
     */
    private function handleMetaWebhook(array $payload): JsonResponse
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value    = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];

                foreach ($messages as $message) {
                    $from = $message['from'] ?? null;
                    $type = $message['type'] ?? 'unknown';

                    if (! $from) {
                        continue;
                    }

                    // Normalize phone number to E.164
                    $phone = $this->normalizePhone($from);
                    $text  = '';

                    if ($type === 'text') {
                        $text = $message['text']['body'] ?? '';
                    } elseif ($type === 'interactive') {
                        // Button reply or list reply
                        $text = $message['interactive']['button_reply']['title']
                            ?? $message['interactive']['list_reply']['title']
                            ?? '';
                    }

                    if (! empty(trim($text))) {
                        $this->storeAndDispatch(
                            $phone,
                            trim($text),
                            'meta',
                            $message['id'] ?? null,
                        );
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Evolution API webhook payload.
     *
     * Evolution API sends events like:
     * {
     *   "event": "messages.upsert",
     *   "data": {
     *     "key": { "remoteJid": "923001234567@s.whatsapp.net", "fromMe": false },
     *     "message": { "conversation": "fee status" }
     *   }
     * }
     */
    private function handleEvolutionWebhook(array $payload): JsonResponse
    {
        $event = $payload['event'] ?? '';

        if (! in_array($event, ['messages.upsert', 'messages.update'])) {
            return response()->json(['status' => 'ok']);
        }

        $data = $payload['data'] ?? [];
        $key  = $data['key'] ?? [];

        // Skip messages sent by us
        if ($key['fromMe'] ?? false) {
            return response()->json(['status' => 'ok']);
        }

        $remoteJid = $key['remoteJid'] ?? '';
        $phone     = $this->extractPhoneFromJid($remoteJid);

        if (! $phone) {
            return response()->json(['status' => 'ok']);
        }

        // Extract message text
        $messageObj = $data['message'] ?? [];
        $text = $messageObj['conversation']
            ?? $messageObj['extendedTextMessage']['text']
            ?? $messageObj['buttonsResponseMessage']['selectedDisplayText']
            ?? '';

        if (! empty(trim($text))) {
            $this->storeAndDispatch($phone, trim($text), 'evolution');
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Normalize a phone number to E.164 format (+923001234567).
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // If starts with 0, assume Pakistan (+92)
        if (str_starts_with($digits, '0')) {
            $digits = '92' . substr($digits, 1);
        }

        // If doesn't start with country code, assume Pakistan
        if (strlen($digits) === 10) {
            $digits = '92' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Extract phone number from WhatsApp JID (e.g., "923001234567@s.whatsapp.net").
     */
    private function extractPhoneFromJid(string $jid): ?string
    {
        if (str_contains($jid, '@g.us')) {
            return null; // Skip group messages
        }

        $phone = explode('@', $jid)[0] ?? '';

        if (empty($phone)) {
            return null;
        }

        return $this->normalizePhone($phone);
    }
}
