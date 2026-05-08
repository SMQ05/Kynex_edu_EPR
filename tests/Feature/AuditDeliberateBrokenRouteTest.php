<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditFinding;
use App\Models\AuditRun;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Integration test for the audit framework end-to-end pipeline.
 *
 * Part A (always runs): verifies the deliberate-500 route exists and returns 500.
 *
 * Part B (requires Playwright + running server): runs audit:run against the
 * route and asserts a 5xx critical finding is recorded in the DB.
 *
 * To run Part B:
 *   1. Add the deliberate-500 route to routes/web.php (env-gated):
 *        if (app()->environment(['local', 'testing'])) {
 *            Route::get('/audit-test/deliberate-500', fn() => abort(500));
 *        }
 *   2. php artisan serve (in a separate terminal)
 *   3. RUN_PLAYWRIGHT_TESTS=1 php artisan test --filter=AuditDeliberateBrokenRouteTest
 *
 * The route is NOT added to routes/web.php in this commit because routes/web.php
 * carries unrelated in-progress admission feature changes. It must be added
 * before Phase 2B Playwright tests run.
 */
class AuditDeliberateBrokenRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only run in local/testing — this test must never hit production.
        if (! app()->environment(['local', 'testing'])) {
            $this->markTestSkipped('This test only runs in local/testing environments.');
        }

        // Register the deliberate-500 route for the duration of this test.
        // This makes Part A work via Laravel's HTTP test helpers without a
        // running web server. Part B (Playwright) still needs a real server
        // because it spawns Node as a child process.
        Route::get('/audit-test/deliberate-500', fn () => abort(500, 'Deliberate audit test 500'));
    }

    // ── Part A: Route contract ─────────────────────────────────────────────────

    /** @test */
    public function deliberate_500_route_returns_http_500(): void
    {
        $this->get('/audit-test/deliberate-500')
             ->assertStatus(500);
    }

    // ── Part B: Full Playwright pipeline ──────────────────────────────────────
    //
    // Requires:
    //   - RUN_PLAYWRIGHT_TESTS=1 env var
    //   - php artisan serve running at APP_URL
    //   - Playwright + Chromium installed in the environment
    //   - Demo users seeded for haji-qamar-public-school-BEb3S9

    /** @test */
    public function audit_run_records_critical_5xx_finding_for_deliberate_500(): void
    {
        if (! getenv('RUN_PLAYWRIGHT_TESTS')) {
            $this->markTestSkipped(
                'Set RUN_PLAYWRIGHT_TESTS=1 and run `php artisan serve` first. ' .
                'Also ensure the deliberate-500 route is registered in routes/web.php.'
            );
        }

        $targetUrl = config('app.url') . '/audit-test/deliberate-500';

        $this->artisan('audit:run', [
            '--tenant' => 'haji-qamar-public-school-BEb3S9',
            '--role'   => 'SCHOOL_ADMIN',
            '--url'    => $targetUrl,
        ]);

        $run = AuditRun::on('central')->latest('started_at')->first();
        $this->assertNotNull($run, 'Expected an AuditRun to be created.');

        $finding = AuditFinding::on('central')
            ->where('run_id',      $run->id)
            ->where('finding_type', '5xx')
            ->where('severity',     'critical')
            ->first();

        $this->assertNotNull(
            $finding,
            "Expected a critical 5xx finding for {$targetUrl}. " .
            "Run ID: {$run->id}. " .
            'Check that the deliberate-500 route is accessible from the running server.'
        );

        $this->assertSame($targetUrl, $finding->url);
        $this->assertSame($run->id,   $finding->run_id);
    }
}
