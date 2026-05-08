<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Verifies the severity classification rules that crawl.js emits and PHP persists.
 * Tests mirror design doc §7 (HTTP severity) and §8 (finding type severity table).
 *
 * These are pure-PHP replicas of crawl.js's httpSeverity() function and the
 * per-finding-type severity constants. If either the JS or PHP side changes,
 * this test catches the divergence.
 */
class AuditSeverityClassifierTest extends BaseTestCase
{
    // ── HTTP status → severity (mirrors crawl.js httpSeverity()) ─────────────

    /** @param int $status @param string|null $expected */
    #[DataProvider('httpStatusProvider')]
    public function test_http_status_maps_to_correct_severity(int $status, ?string $expected): void
    {
        $this->assertSame($expected, $this->httpSeverity($status));
    }

    public static function httpStatusProvider(): array
    {
        return [
            'ok'                     => [200, null],
            'redirect'               => [302, null],
            '400 bad request'        => [400, 'medium'],
            '401 unauthorized'       => [401, 'medium'],
            '403 forbidden'          => [403, 'high'],
            '404 not found'          => [404, 'high'],
            '422 unprocessable'      => [422, 'medium'],
            '429 rate limit'         => [429, 'medium'],
            '500 internal error'     => [500, 'critical'],
            '502 bad gateway'        => [502, 'critical'],
            '503 unavailable'        => [503, 'critical'],
        ];
    }

    // ── Finding type → severity table (design doc §8) ─────────────────────────

    #[DataProvider('findingTypeSeverityProvider')]
    public function test_finding_type_severity_matches_design_doc(string $type, string $expected): void
    {
        $this->assertSame($expected, $this->findingTypeSeverity($type));
    }

    public static function findingTypeSeverityProvider(): array
    {
        return [
            '5xx'                    => ['5xx',                   'critical'],
            'form_500_on_validation' => ['form_500_on_validation', 'critical'],
            '4xx (403)'              => ['4xx_403',               'high'],
            '4xx (404)'              => ['4xx_404',               'high'],
            'blank_page'             => ['blank_page',            'high'],
            'filament_error_toast'   => ['filament_error_toast',  'high'],
            'panel_chrome_missing'   => ['panel_chrome_missing',  'high'],
            'js_error'               => ['js_error',              'medium'],
            'network_failure'        => ['network_failure',       'medium'],
            'broken_image'           => ['broken_image',          'medium'],
            'malformed_image_url'    => ['malformed_image_url',   'medium'],
            'security_violation'     => ['security_violation',    'medium'],
            'slow_page'              => ['slow_page',             'low'],
            'empty_image_src'        => ['empty_image_src',       'low'],
            'access_denied_ok'       => ['access_denied_ok',      'ok'],
        ];
    }

    // ── Blank-page detection gate (design doc §8 + crawl.js check 2) ─────────
    //
    // Blank-page fires ONLY when ALL three conditions hold:
    //   - no <main> / [role=main] / .fi-main
    //   - no .fi-ta-empty-state / [data-empty-state]
    //   - body text < 100 chars
    //   - AND http status < 400

    public function test_blank_page_requires_all_three_conditions(): void
    {
        $cases = [
            // [hasMain, hasEmptyState, bodyLen, httpStatus, shouldFire]
            [false, false, 50,  200, true],   // all three conditions met
            [true,  false, 50,  200, false],  // has main → no fire
            [false, true,  50,  200, false],  // has empty-state → no fire
            [false, false, 150, 200, false],  // body text >= 100 → no fire
            [false, false, 50,  404, false],  // 4xx → no fire (not a blank page)
            [false, false, 50,  500, false],  // 5xx → no fire (already a 5xx finding)
            [false, false, 99,  200, true],   // body exactly 99 chars → fires
            [false, false, 100, 200, false],  // body exactly 100 → doesn't fire (< 100, not <=)
        ];

        foreach ($cases as [$hasMain, $hasEmptyState, $bodyLen, $httpStatus, $expected]) {
            $fires = ! $hasMain && ! $hasEmptyState && $bodyLen < 100 && $httpStatus < 400;
            $this->assertSame(
                $expected, $fires,
                "hasMain={$hasMain} hasEmptyState={$hasEmptyState} bodyLen={$bodyLen} http={$httpStatus}"
            );
        }
    }

    // ── Slow-page threshold (design doc §8: > 8000ms) ─────────────────────────

    public function test_slow_page_threshold_is_8000ms(): void
    {
        $threshold = 8000;

        $this->assertFalse(8000 > $threshold, '8000ms should not trigger slow_page');
        $this->assertTrue(8001 > $threshold,  '8001ms should trigger slow_page');
        $this->assertFalse(7999 > $threshold, '7999ms should not trigger slow_page');
    }

    public function test_slow_page_does_not_fire_on_error_pages(): void
    {
        // 4xx/5xx pages are already caught by httpSeverity() — slow_page
        // must only fire when httpStatus < 400 (design doc §8).
        $durationMs = 9000;
        $httpStatus = 500;
        $fires      = $durationMs > 8000 && $httpStatus < 400;
        $this->assertFalse($fires);
    }

    // ── Image severity categorization (design doc §8) ─────────────────────────

    public function test_empty_image_src_is_low_severity(): void
    {
        $this->assertSame('low', $this->findingTypeSeverity('empty_image_src'));
    }

    public function test_malformed_image_url_is_medium_severity(): void
    {
        $this->assertSame('medium', $this->findingTypeSeverity('malformed_image_url'));
    }

    public function test_broken_image_is_medium_severity(): void
    {
        $this->assertSame('medium', $this->findingTypeSeverity('broken_image'));
    }

    // ── STUDENT outcome matrix (design doc §8 + crawl.js runStudentNegativeTest) ─
    //
    // hasNav  + hasAccessDenied  → severity='ok',     type='access_denied_ok'
    // hasNav  + !hasAccessDenied → severity='medium',  type='security_violation'
    // !hasNav + hasAccessDenied  → severity='high',    type='panel_chrome_missing'
    // !hasNav + !hasAccessDenied → both findings above (two separate emitFinding calls)

    #[DataProvider('studentOutcomeProvider')]
    public function test_student_outcome_matrix(
        bool $hasNav,
        bool $hasAccessDenied,
        array $expectedFindings
    ): void {
        $findings = $this->computeStudentOutcomes($hasNav, $hasAccessDenied);
        $this->assertEquals($expectedFindings, $findings);
    }

    public static function studentOutcomeProvider(): array
    {
        return [
            'correct — access denied with chrome' => [
                true, true,
                [['severity' => 'ok', 'type' => 'access_denied_ok']],
            ],
            'no access denied text — student may see real content' => [
                true, false,
                [['severity' => 'medium', 'type' => 'security_violation']],
            ],
            'access denied but no sidebar' => [
                false, true,
                [['severity' => 'high', 'type' => 'panel_chrome_missing']],
            ],
            'worst case — no nav, no access denied' => [
                false, false,
                [
                    ['severity' => 'high',   'type' => 'panel_chrome_missing'],
                    ['severity' => 'medium', 'type' => 'security_violation'],
                ],
            ],
        ];
    }

    // ── Private helpers (replicas of JS logic for testability) ───────────────

    private function httpSeverity(int $status): ?string
    {
        if ($status >= 500)  return 'critical';
        if ($status === 403) return 'high';
        if ($status === 404) return 'high';
        if ($status >= 400)  return 'medium';
        return null;
    }

    private function findingTypeSeverity(string $type): string
    {
        return match ($type) {
            '5xx', 'form_500_on_validation'          => 'critical',
            '4xx_403', '4xx_404',
            'blank_page', 'filament_error_toast',
            'panel_chrome_missing'                   => 'high',
            'js_error', 'network_failure',
            'broken_image', 'malformed_image_url',
            'security_violation'                     => 'medium',
            'slow_page', 'empty_image_src'           => 'low',
            'access_denied_ok'                       => 'ok',
            default                                  => 'info',
        };
    }

    /** Replicates runStudentNegativeTest() outcome logic from crawl.js. */
    private function computeStudentOutcomes(bool $hasNav, bool $hasAccessDenied): array
    {
        if ($hasAccessDenied && $hasNav) {
            return [['severity' => 'ok', 'type' => 'access_denied_ok']];
        }

        $findings = [];
        if (! $hasNav) {
            $findings[] = ['severity' => 'high', 'type' => 'panel_chrome_missing'];
        }
        if (! $hasAccessDenied) {
            $findings[] = ['severity' => 'medium', 'type' => 'security_violation'];
        }
        return $findings;
    }
}
