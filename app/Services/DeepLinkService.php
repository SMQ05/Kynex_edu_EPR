<?php

declare(strict_types=1);

namespace App\Services;

/**
 * DeepLinkService — Generates tenant-aware deep link URLs for notifications.
 *
 * Returns a URL based on event type so that in-app notifications,
 * SMS, and WhatsApp messages can include actionable links.
 *
 * Mobile deep links use app-specific schemes to avoid disambiguation
 * when both apps are installed on the same device:
 *   - kynexedu-mgmt://    (management app)
 *   - kynexedu-parent://  (student/parent app)
 */
class DeepLinkService
{
    /**
     * Deep link schemes per app type.
     * Must match the "scheme" value in each app's app.json.
     */
    private const MOBILE_SCHEMES = [
        'management'     => 'kynexedu-mgmt',
        'student_parent' => 'kynexedu-parent',
    ];

    /**
     * Generate a deep link URL for a given event trigger.
     *
     * @param  string  $eventTrigger  The event type (e.g., 'student.absent', 'fee.overdue')
     * @param  array   $context       Contextual data for building the URL
     * @return string|null            The deep link URL or null if no mapping exists
     */
    public static function generate(string $eventTrigger, array $context = []): ?string
    {
        return match ($eventTrigger) {
            'student.absent' => static::tenantRoute(
                'filament.school.resources.attendance-records.index',
                array_filter([
                    'date'       => $context['date'] ?? null,
                    'student_id' => $context['student_id'] ?? null,
                ])
            ),

            'fee.overdue' => static::tenantRoute(
                'filament.school.pages.collect-fee',
                array_filter([
                    'student' => $context['student_id'] ?? null,
                ])
            ),

            'exam.result_published' => static::tenantRoute(
                'filament.school.pages.exam-results',
                array_filter([
                    'exam_id' => $context['exam_id'] ?? null,
                ])
            ),

            'leave.approved', 'leave.rejected' => static::tenantRoute(
                'filament.school.resources.leave-requests.index'
            ),

            'approval.requested' => static::tenantRoute(
                'filament.school.pages.pending-approvals'
            ),

            'approval.approved', 'approval.rejected' => static::tenantRoute(
                'filament.school.pages.pending-approvals'
            ),

            'monthly_billing' => static::tenantRoute(
                'filament.school.pages.billing-statement'
            ),

            // Phase 9.6 — deep link for notice notifications
            'notice.published' => static::tenantRoute(
                'filament.school.resources.notices.index',
                array_filter([
                    'notice_id' => $context['notice_id'] ?? null,
                ])
            ),

            default => null,
        };
    }

    /**
     * Build a tenant-aware URL.
     *
     * Uses the current tenant context to construct a subdomain-based URL.
     * Falls back to a relative path if tenancy is not initialised.
     */
    private static function tenantRoute(string $name, array $params = []): string
    {
        $tenant = function_exists('tenant') ? tenant() : null;

        if ($tenant) {
            $base = "https://{$tenant->id}.kynexedu.com";
        } else {
            $base = config('app.url', 'https://kynexedu.com');
        }

        // Attempt to resolve the named route; fall back to path-based construction
        try {
            $path = route($name, $params, false);
        } catch (\Throwable) {
            // Route may not exist yet — build a best-effort path
            $path = '/' . str_replace('.', '/', str_replace('filament.school.', '', $name));
            if (! empty($params)) {
                $path .= '?' . http_build_query($params);
            }
        }

        return $base . $path;
    }

    /**
     * Generate a mobile deep link URL with the correct app-specific scheme.
     *
     * @param  string  $appType  'management' or 'student_parent' (matches AppType enum)
     * @param  string  $path     Path portion (e.g., 'exam/result/01HYXYZ', 'fee/due/01HYABC')
     * @return string            Deep link URL (e.g., 'kynexedu-parent://fee/due/01HYABC')
     */
    public static function generateMobileDeepLink(string $appType, string $path): string
    {
        $scheme = self::MOBILE_SCHEMES[$appType] ?? self::MOBILE_SCHEMES['student_parent'];

        return $scheme . '://' . ltrim($path, '/');
    }
}
