<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiContentDetector — OPTIONAL, PREMIUM, OPT-IN AI-originality detection.
 *
 * Academic-integrity policy (guide.md §3): KynexEdu never AI-auto-grades
 * homework. This service does NOT grade — it only estimates how likely a text
 * was AI-written, as an advisory signal a human can act on. It is OFF by
 * default and only runs when a school has explicitly enabled + configured an
 * originality provider (separate paid subscription, e.g. Originality.ai).
 *
 * Pluggable-driver design (interface-style with a self-resolving factory):
 *   - resolve()           → picks the configured driver for the current tenant
 *   - detect(string)      → array{ai_score, human_score, driver, ...}
 *   - OriginalityAiDriver → real Originality.ai HTTP driver
 *   - NullDriver          → safe no-op when nothing is configured
 *
 * API key source (in priority order):
 *   1. tenant column/attribute `originality_api_key` (per-school BYO key)
 *   2. tenant `settings['originality_api_key']` (if the tenant carries a JSON
 *      settings bag — accessed defensively, never assumed)
 *   3. env ORIGINALITY_API_KEY (platform fallback)
 */
class AiContentDetector
{
    /**
     * Build the detector for the current tenant, choosing a driver based on
     * configuration. Falls back to the Null driver when not configured.
     */
    public static function forCurrentTenant(): AiContentDetectorDriver
    {
        $tenant = tenancy()->tenant;
        $tenant = $tenant instanceof Tenant ? $tenant : null;

        return self::resolve($tenant);
    }

    public static function resolve(?Tenant $tenant): AiContentDetectorDriver
    {
        if (! self::enabledFor($tenant)) {
            return new NullDriver('Originality detection is not enabled for this school.');
        }

        $apiKey = self::apiKey($tenant);
        if ($apiKey === null || $apiKey === '') {
            return new NullDriver('No Originality.ai API key configured.');
        }

        $driver = self::driverName($tenant);

        return match ($driver) {
            'originality', 'originality_ai' => new OriginalityAiDriver($apiKey),
            'null', 'off'                   => new NullDriver('Detector disabled.'),
            default                          => new OriginalityAiDriver($apiKey),
        };
    }

    /**
     * Whether AI-originality detection is opt-in-enabled for the tenant.
     * Read defensively — these attributes/flags may not exist on every tenant.
     */
    public static function enabledFor(?Tenant $tenant): bool
    {
        if (! $tenant instanceof Tenant) {
            return false;
        }

        // 1) Explicit boolean attribute if the column exists.
        $flag = self::attr($tenant, 'originality_detection_enabled');
        if ($flag !== null) {
            return (bool) $flag;
        }

        // 2) Settings bag toggle.
        $fromSettings = self::settingsValue($tenant, 'originality_detection_enabled');
        if ($fromSettings !== null) {
            return (bool) $fromSettings;
        }

        // 3) Implicitly enabled if a key is present anywhere (BYO key = opt-in).
        return self::apiKey($tenant) !== null && self::apiKey($tenant) !== '';
    }

    private static function driverName(?Tenant $tenant): string
    {
        $driver = self::attr($tenant, 'originality_driver')
            ?? self::settingsValue($tenant, 'originality_driver')
            ?? config('services.originality.driver', env('ORIGINALITY_DRIVER', 'originality'));

        return is_string($driver) && $driver !== '' ? $driver : 'originality';
    }

    private static function apiKey(?Tenant $tenant): ?string
    {
        $key = self::attr($tenant, 'originality_api_key')
            ?? self::settingsValue($tenant, 'originality_api_key')
            ?? config('services.originality.api_key', env('ORIGINALITY_API_KEY'));

        return is_string($key) && $key !== '' ? $key : null;
    }

    /** Safely read a tenant model attribute that may not be a defined column. */
    private static function attr(?Tenant $tenant, string $key): mixed
    {
        if (! $tenant instanceof Tenant) {
            return null;
        }

        try {
            $val = $tenant->getAttribute($key);

            return $val === '' ? null : $val;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Safely read a key from a tenant JSON `settings`/`data` bag if present. */
    private static function settingsValue(?Tenant $tenant, string $key): mixed
    {
        if (! $tenant instanceof Tenant) {
            return null;
        }

        foreach (['settings', 'data', 'meta'] as $bag) {
            try {
                $value = $tenant->getAttribute($bag);
            } catch (\Throwable) {
                continue;
            }
            if (is_array($value) && array_key_exists($key, $value)) {
                return $value[$key];
            }
        }

        return null;
    }
}

/**
 * Common contract every detector driver implements.
 */
interface AiContentDetectorDriver
{
    /**
     * @return array{
     *   ai_score: float,        // 0..1 likelihood text is AI-generated
     *   human_score: float,     // 0..1 likelihood text is human-written
     *   classification: string, // human|mixed|ai|unknown
     *   driver: string,
     *   available: bool,        // false when no real check ran
     *   message?: string,
     *   raw?: array<mixed>
     * }
     */
    public function detect(string $text): array;

    public function isAvailable(): bool;
}

/**
 * Originality.ai scan driver. POSTs the text to the Originality.ai scan API
 * and normalises the response into the shared shape. Network/parse failures
 * degrade gracefully to an "unavailable" result rather than throwing.
 *
 * @see https://docs.originality.ai
 */
class OriginalityAiDriver implements AiContentDetectorDriver
{
    private string $baseUrl;

    public function __construct(
        private readonly string $apiKey,
    ) {
        $this->baseUrl = rtrim(
            (string) config('services.originality.base_url', env('ORIGINALITY_BASE_URL', 'https://api.originality.ai/api/v1')),
            '/',
        );
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function detect(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return $this->unavailable('Empty text — nothing to scan.');
        }

        try {
            $response = Http::withHeaders([
                'X-OAI-API-KEY' => $this->apiKey,
                'Accept'        => 'application/json',
            ])
                ->timeout(60)
                ->post($this->baseUrl . '/scan/ai', [
                    'content'      => $text,
                    'aiModelVersion' => config('services.originality.model', env('ORIGINALITY_MODEL', 'multilingual')),
                    'storeScan'    => false,
                ]);

            if (! $response->successful()) {
                Log::warning('Originality.ai scan failed', [
                    'status' => $response->status(),
                ]);

                return $this->unavailable('Originality.ai request failed: HTTP ' . $response->status());
            }

            $data = $response->json();

            // Originality.ai returns { score: { ai: 0..1, original: 0..1 }, ... }
            $score    = is_array($data['score'] ?? null) ? $data['score'] : [];
            $aiScore  = self::clamp((float) ($score['ai'] ?? ($data['ai_score'] ?? 0)));
            $human    = array_key_exists('original', $score)
                ? self::clamp((float) $score['original'])
                : self::clamp(1.0 - $aiScore);

            return [
                'ai_score'       => $aiScore,
                'human_score'    => $human,
                'classification' => self::classify($aiScore),
                'driver'         => 'originality_ai',
                'available'      => true,
                'raw'            => is_array($data) ? $data : [],
            ];
        } catch (\Throwable $e) {
            Log::warning('Originality.ai exception', ['error' => $e->getMessage()]);

            return $this->unavailable('Originality.ai unavailable: ' . $e->getMessage());
        }
    }

    private function unavailable(string $message): array
    {
        return [
            'ai_score'       => 0.0,
            'human_score'    => 0.0,
            'classification' => 'unknown',
            'driver'         => 'originality_ai',
            'available'      => false,
            'message'        => $message,
        ];
    }

    private static function clamp(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }

    private static function classify(float $aiScore): string
    {
        return match (true) {
            $aiScore >= 0.75 => 'ai',
            $aiScore >= 0.40 => 'mixed',
            default          => 'human',
        };
    }
}

/**
 * No-op driver used when detection is off or unconfigured. Always reports
 * "unavailable" so callers can surface a clear "not enabled" message without
 * branching on null.
 */
class NullDriver implements AiContentDetectorDriver
{
    public function __construct(
        private readonly string $reason = 'AI-originality detection is not configured.',
    ) {}

    public function isAvailable(): bool
    {
        return false;
    }

    public function detect(string $text): array
    {
        return [
            'ai_score'       => 0.0,
            'human_score'    => 0.0,
            'classification' => 'unknown',
            'driver'         => 'null',
            'available'      => false,
            'message'        => $this->reason,
        ];
    }
}
