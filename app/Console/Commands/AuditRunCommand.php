<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditFinding;
use App\Models\AuditRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class AuditRunCommand extends Command
{
    protected $signature = 'audit:run
        {--tenant=              : Target a specific tenant slug}
        {--role=                : Target a specific role (requires --tenant)}
        {--url=                 : Single-URL test (requires --tenant and --role)}
        {--all                  : Crawl every eligible tenant × every role}
        {--skip-saas            : Skip SaaS admin panel even when --all is given}
        {--force-tenant         : Bypass blocked-tenant guard (not recommended)}
        {--max-urls=0           : Cap URLs per role (0 = unlimited)}
        {--page-timeout=30000   : Per-page navigation timeout in ms}
        {--role-timeout=1200000 : Per-role total timeout in ms}
        {--total-timeout=5400000: Total run timeout in ms}
        {--no-headless          : Run browser with visible UI (debug mode)}';

    protected $description = 'Crawl KynexEdu panels and record audit findings';

    // ── Constants ─────────────────────────────────────────────────────────

    /**
     * BLOCKED_TENANTS is hardcoded for Phase 2A. This is interim — eventually
     * tenant eligibility should be derived from data: a tenant is "auditable"
     * if it has a verified custom domain (domains table: is_verified=true AND
     * domain_type='custom') OR if a wildcard subdomain config is in place.
     *
     * When the second school onboards without a custom domain, replace this
     * constant with a query in buildJobList() against the domains table.
     *
     * Tracked in docs/audit-framework-design-2026-05-08.md §16 follow-up.
     */
    private const BLOCKED_TENANTS = [
        'al-qasim-school-HStisi' => <<<'MSG'
ERROR: al-qasim-school-HStisi access requires either:
  (a) A verified custom domain configured in the SaaS admin UI, OR
  (b) Wildcard *.sms.kynexsolutions.com DNS record + wildcard TLS cert
Neither is currently configured. Aborting.
To skip this check and attempt anyway, use --force-tenant (not recommended).
MSG,
    ];

    /** Eligible tenants for --all. Grouped by tenant slug. */
    private const ALL_TENANTS = [
        'haji-qamar-public-school-BEb3S9' => [
            'login_url'  => 'https://aqmdigital.com/login',
            'panel_url'  => 'https://aqmdigital.com/admin',
            'parent_url' => 'https://aqmdigital.com/parent',
        ],
    ];

    /**
     * Roles crawled per school tenant. Order defines job sequence — all roles
     * for one tenant are grouped before moving to the next (parallelism-ready).
     * STUDENT is a negative test: verifies AccessDenied renders with full chrome.
     */
    private const SCHOOL_ROLES = [
        'SCHOOL_ADMIN'   => ['roleKey' => 'admin',          'panel' => 'admin'],
        'INSTITUTE_HEAD' => ['roleKey' => 'principal',      'panel' => 'admin'],
        'REGISTRAR'      => ['roleKey' => 'vice-principal', 'panel' => 'admin'],
        'ACCOUNTANT'     => ['roleKey' => 'accountant',     'panel' => 'admin'],
        'TEACHER'        => ['roleKey' => 'teacher',        'panel' => 'admin'],
        'PARENT'         => ['roleKey' => 'parent',         'panel' => 'parent'],
        'STUDENT'        => ['roleKey' => 'student',        'panel' => 'admin'],
    ];

    /** @var array<int, array{tenant: string, role: string}> Roles skipped due to missing seeded users — recorded in run meta. */
    private array $skippedRoles = [];

    // ── Entry point ───────────────────────────────────────────────────────

    public function handle(): int
    {
        if (! $this->validateOptions()) {
            return self::FAILURE;
        }

        if (! $this->checkPlaywright()) {
            return self::FAILURE;
        }

        $jobs = $this->buildJobList();
        if (empty($jobs)) {
            $this->error('No jobs to run. Check --tenant/--role/--all options.');
            return self::FAILURE;
        }

        $resolved = $this->resolveSaasCredential($jobs);
        if ($resolved === null) {
            return self::FAILURE; // hard abort: explicit --tenant=saas, no password
        }
        [$jobs, $saasMeta] = $resolved;

        if (empty($jobs)) {
            $this->error('No jobs remaining after credential resolution.');
            return self::FAILURE;
        }

        $scope = $this->resolveScope();

        $run = AuditRun::create([
            'id'               => (string) Str::uuid(),
            'scope'            => $scope,
            'tenant_slug'      => $this->option('tenant'),
            'role'             => $this->option('role'),
            'url'              => $this->option('url'),
            'status'           => 'running',
            'saas_skipped'     => $saasMeta['skipped'],
            'saas_skip_reason' => $saasMeta['reason'],
            'started_at'       => now(),
            'meta'             => [],
        ]);

        $this->info("Audit run {$run->id} started — {$scope} scope.");

        if ($saasMeta['skipped']) {
            $this->warn("SaaS panel: skipped ({$saasMeta['reason']})");
        }

        $deadline   = now()->addMilliseconds((int) $this->option('total-timeout'));
        $exitStatus = self::SUCCESS;

        foreach ($jobs as $job) {
            if (now()->gt($deadline)) {
                $this->flushRunRecord($run, 'timeout', $job);
                $exitStatus = self::FAILURE;
                break;
            }

            $result = $this->runJob($run, $job, $deadline);

            if ($result === 'timeout') {
                // streamJobOutput already updated last_url/role on the run record
                $exitStatus = self::FAILURE;
                break;
            }

            if ($result === 'error') {
                $this->warn(sprintf(
                    '  [%s/%s] Node process error — continuing to next role.',
                    $job['tenant_slug'], $job['role']
                ));
            }
        }

        $this->finaliseRun($run, $exitStatus === self::SUCCESS ? 'complete' : 'timeout');
        $this->printSummary($run->fresh(), $saasMeta);

        return $exitStatus;
    }

    // ── Group 1: Options validation + environment checks ──────────────────

    private function validateOptions(): bool
    {
        $hasAll    = $this->option('all');
        $hasTenant = (bool) $this->option('tenant');
        $hasRole   = (bool) $this->option('role');
        $hasUrl    = (bool) $this->option('url');

        if ($hasAll && ($hasTenant || $hasRole || $hasUrl)) {
            $this->error('--all cannot be combined with --tenant, --role, or --url.');
            return false;
        }

        if ($hasRole && ! $hasTenant) {
            $this->error('--role requires --tenant.');
            return false;
        }

        if ($hasUrl && (! $hasTenant || ! $hasRole)) {
            $this->error('--url requires both --tenant and --role.');
            return false;
        }

        if (! $hasAll && ! $hasTenant) {
            $this->error('Specify --all to crawl all eligible tenants, or --tenant=<slug> for a specific tenant.');
            return false;
        }

        // Blocked tenant guard — fail loudly so the remediation message is seen
        if ($hasTenant && ! $this->option('force-tenant')) {
            $slug = (string) $this->option('tenant');
            if (isset(self::BLOCKED_TENANTS[$slug])) {
                $this->error(trim(self::BLOCKED_TENANTS[$slug]));
                return false;
            }
        }

        return true;
    }

    private function checkPlaywright(): bool
    {
        $node = new Process(['node', '--version']);
        $node->run();
        if (! $node->isSuccessful()) {
            $this->error('Node.js is not available. Install Node.js 18+ before running the audit crawler.');
            return false;
        }

        $pw = new Process(['node', '-e', "require('playwright'); process.exit(0);"]);
        $pw->run();
        if (! $pw->isSuccessful()) {
            $this->error("Playwright is not installed. Run:\n  npx playwright install chromium");
            return false;
        }

        $script = storage_path('audit-scripts/crawl.js');
        if (! file_exists($script)) {
            // crawl.js MUST be baked into the image via Dockerfile COPY — never
            // docker cp'd. Otherwise the audit infrastructure breaks at the
            // next container recreate.
            $this->error("Crawl script not found: {$script}\nDeploy the latest code first.");
            return false;
        }

        return true;
    }

    // ── Group 2: Job list construction + credential resolution ────────────

    /**
     * Builds the ordered job list. Jobs are grouped by tenant (all roles for
     * tenant A, then all for tenant B) to keep tenancy initialization bounded
     * and to make future role-level parallelism natural.
     *
     * The SaaS job is added unconditionally when in scope.
     * resolveSaasCredential() decides whether it actually runs.
     */
    private function buildJobList(): array
    {
        if ($this->option('url')) {
            $job = $this->buildSingleUrlJob(
                (string) $this->option('tenant'),
                (string) $this->option('role'),
                (string) $this->option('url'),
            );
            return $job ? [$job] : [];
        }

        if ($this->option('tenant') && $this->option('role')) {
            $job = $this->buildJobForRole(
                (string) $this->option('tenant'),
                strtoupper((string) $this->option('role')),
            );
            return $job ? [$job] : [];
        }

        if ($this->option('tenant')) {
            return $this->buildJobsForTenant((string) $this->option('tenant'));
        }

        // --all: every eligible tenant grouped, SaaS last
        $jobs = [];
        foreach (array_keys(self::ALL_TENANTS) as $slug) {
            array_push($jobs, ...$this->buildJobsForTenant($slug));
        }

        if (! $this->option('skip-saas')) {
            $jobs[] = $this->buildSaasJob();
        }

        return $jobs;
    }

    /**
     * All roles for one school tenant, tenant-DB-grouped.
     *
     * DB context lifecycle:
     *   ENTRY   — central DB active (Tenant::find uses central connection)
     *   try     — tenancy()->initialize() switches to tenant DB
     *   try     — SchoolUser queries run against tenant DB
     *   finally — tenancy()->end() restores central DB (only if initialize ran)
     *   EXIT    — central DB active
     *
     * If a role has no seeded user, that role's job is skipped with a warning.
     * Remaining roles for the tenant continue. Skipped roles are recorded in
     * $this->skippedRoles[] for inclusion in run metadata.
     */
    private function buildJobsForTenant(string $tenantSlug): array
    {
        $config = self::ALL_TENANTS[$tenantSlug] ?? null;
        if (! $config) {
            $this->error("Tenant {$tenantSlug} is not in the eligible tenant list. Add it to ALL_TENANTS if it has a verified custom domain.");
            return [];
        }

        $usersByRole = [];
        $initialized = false;

        try {
            $tenant = \App\Models\Tenant::find($tenantSlug); // central DB
            if (! $tenant) {
                $this->warn("Tenant {$tenantSlug} not found in central DB — all roles skipped.");
                return [];
            }

            tenancy()->initialize($tenant); // switch to tenant DB
            $initialized = true;

            foreach (array_keys(self::SCHOOL_ROLES) as $role) {
                $user = \App\Models\SchoolUser::whereHas(
                    'roles',
                    fn ($q) => $q->where('name', $role)
                )->first(['name', 'email']);

                if ($user) {
                    $usersByRole[$role] = $user->email;
                } else {
                    $this->warn("  [{$tenantSlug}] No user with role {$role} — job skipped.");
                    $this->skippedRoles[] = ['tenant' => $tenantSlug, 'role' => $role];
                }
            }
        } finally {
            if ($initialized) {
                tenancy()->end(); // always restore central DB context
            }
        }

        $jobs = [];
        foreach (self::SCHOOL_ROLES as $role => $meta) {
            if (! isset($usersByRole[$role])) {
                continue;
            }

            $panelUrl = $meta['panel'] === 'parent'
                ? $config['parent_url']
                : $config['panel_url'];

            $jobs[] = [
                'tenant_slug' => $tenantSlug,
                'role'        => $role,
                'role_key'    => $meta['roleKey'],
                'email'       => $usersByRole[$role],
                'panel'       => $meta['panel'],
                'login_url'   => $config['login_url'],
                'panel_url'   => $panelUrl,
                'single_url'  => null,
            ];
        }

        return $jobs;
    }

    /**
     * Single role for --tenant + --role invocation.
     * Same DB context lifecycle as buildJobsForTenant() but for one role only.
     */
    private function buildJobForRole(string $tenantSlug, string $role): ?array
    {
        if (! isset(self::SCHOOL_ROLES[$role])) {
            $this->error("Unknown role: {$role}. Valid: " . implode(', ', array_keys(self::SCHOOL_ROLES)));
            return null;
        }

        $config = self::ALL_TENANTS[$tenantSlug] ?? null;
        if (! $config) {
            $this->error("Tenant {$tenantSlug} is not in the eligible tenant list.");
            return null;
        }

        $email       = null;
        $initialized = false;

        try {
            $tenant = \App\Models\Tenant::find($tenantSlug); // central DB
            if (! $tenant) {
                $this->error("Tenant {$tenantSlug} not found in central DB.");
                return null;
            }

            tenancy()->initialize($tenant); // switch to tenant DB
            $initialized = true;

            $user  = \App\Models\SchoolUser::whereHas(
                'roles',
                fn ($q) => $q->where('name', $role)
            )->first(['email']);
            $email = $user?->email;
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }

        if (! $email) {
            $this->error("No user with role {$role} found in {$tenantSlug}.");
            return null;
        }

        $meta     = self::SCHOOL_ROLES[$role];
        $panelUrl = $meta['panel'] === 'parent' ? $config['parent_url'] : $config['panel_url'];

        return [
            'tenant_slug' => $tenantSlug,
            'role'        => $role,
            'role_key'    => $meta['roleKey'],
            'email'       => $email,
            'panel'       => $meta['panel'],
            'login_url'   => $config['login_url'],
            'panel_url'   => $panelUrl,
            'single_url'  => null,
        ];
    }

    private function buildSingleUrlJob(string $tenantSlug, string $role, string $url): ?array
    {
        $job = $this->buildJobForRole($tenantSlug, strtoupper($role));
        if (! $job) {
            return null;
        }
        $job['single_url'] = $url;
        return $job;
    }

    /**
     * SaaS admin job. No tenant DB query — there is exactly one SaaS admin
     * (admin@kynexedu.com). Password is NOT stored in the job array;
     * runJob() reads config('audit.saas_admin_password') at execution time.
     */
    private function buildSaasJob(): array
    {
        return [
            'tenant_slug' => 'saas',
            'role'        => 'saas_admin',
            'role_key'    => 'saas',
            'email'       => 'admin@kynexedu.com',
            'panel'       => 'saas',
            'login_url'   => 'https://sms.kynexsolutions.com/saas/login',
            'panel_url'   => 'https://sms.kynexsolutions.com/saas',
            'single_url'  => null,
        ];
    }

    /**
     * Resolves SaaS panel credential availability and decides whether the SaaS
     * job runs. This is the single decision point for SaaS inclusion.
     *
     *   config key present              → keep job, return skipped=false
     *   key missing + explicit --tenant=saas → hard abort (returns null)
     *   key missing + --all             → strip SaaS job, warn, return skipped=true
     *   no SaaS job in list (--skip-saas) → pass through, skipped=false
     *
     * Returns null to signal a hard abort; handle() returns FAILURE.
     * Password is never placed in the job struct — runJob() reads config() directly.
     *
     * @return array{array, array{skipped: bool, reason: string|null}}|null
     */
    private function resolveSaasCredential(array $jobs): ?array
    {
        $hasSaasJob = collect($jobs)->contains('tenant_slug', 'saas');
        $isExplicit = $this->option('tenant') === 'saas';

        if (! $hasSaasJob) {
            return [$jobs, ['skipped' => false, 'reason' => null]];
        }

        if (config('audit.saas_admin_password')) {
            return [$jobs, ['skipped' => false, 'reason' => null]];
        }

        if ($isExplicit) {
            $this->error(
                "AUDIT_SAAS_ADMIN_PASSWORD not set.\n" .
                "Set it in .env.production or use --skip-saas to omit the SaaS panel."
            );
            return null; // hard abort
        }

        $this->warn(
            'AUDIT_SAAS_ADMIN_PASSWORD not set — SaaS panel skipped. ' .
            'Set it in .env.production to include SaaS panel coverage.'
        );

        $jobs = array_values(array_filter($jobs, fn ($j) => $j['tenant_slug'] !== 'saas'));

        return [$jobs, ['skipped' => true, 'reason' => 'env_missing']];
    }

    // ── Group 3: Job execution, IPC, finding persistence ─────────────────

    private function runJob(AuditRun $run, array $job, \Carbon\Carbon $deadline): string
    {
        $credsPath    = '';
        $credsDeleted = false;

        try {
            // ── 1. Derive password (never logged, never in job array) ───────
            $password = $job['panel'] === 'saas'
                ? (string) config('audit.saas_admin_password')
                : $this->derivePassword($job['role_key'], $job['email']);

            // ── 2. Write 0600 temp credentials file ─────────────────────────
            $credsPath = storage_path(
                'audit-tmp/' . $run->id . '-' . $job['tenant_slug'] . '-' . $job['role'] . '.json'
            );
            $this->writeCredsFile($credsPath, $job['email'], $password);
            unset($password); // clear local reference immediately after file write

            // ── 3. Build and start Node Playwright process ──────────────────
            $process = $this->buildCrawlProcess($run, $job, $credsPath, $deadline);
            $process->start();

            $this->line(sprintf(
                '  → [%s / %s] crawl started (PID %d)',
                $job['tenant_slug'], $job['role'], $process->getPid() ?? 0
            ));

            // ── 4. Stream NDJSON until process exits ────────────────────────
            return $this->streamJobOutput($process, $run, $job, $credsPath, $credsDeleted, $deadline);

        } finally {
            // Always delete creds file — catches crashes, timeouts, exceptions.
            // $credsDeleted is true only if login_complete was received normally.
            if (! $credsDeleted && $credsPath !== '' && file_exists($credsPath)) {
                @unlink($credsPath);
            }
        }
    }

    /**
     * Streams NDJSON from the running Node process until it terminates.
     *
     * Partial-line handling: stdout arrives in arbitrary byte chunks.
     * We maintain $buffer and only parse newline-terminated lines.
     * array_pop() after explode("\n") leaves the last (possibly partial)
     * line in $buffer until more bytes arrive or the process exits.
     *
     * Timeout precedence:
     *   1. Per-page — enforced inside Node (Playwright navigation timeout)
     *   2. Per-role — enforced here via $process->checkTimeout() (Symfony)
     *   3. Total-run deadline — enforced here by comparing now() to $deadline
     */
    private function streamJobOutput(
        Process $process,
        AuditRun $run,
        array $job,
        string $credsPath,
        bool &$credsDeleted,
        \Carbon\Carbon $deadline
    ): string {
        $buffer       = '';
        $lastUrl      = $job['panel_url'];
        $pagesCrawled = 0;

        $dispatch = function (array $event) use (
            $run, $job, $credsPath, &$credsDeleted, &$lastUrl, &$pagesCrawled
        ): void {
            switch ($event['event'] ?? '') {
                case 'login_complete':
                    // Delete creds file the instant Node confirms login —
                    // earliest safe moment; Node has the session cookie now.
                    if (! $credsDeleted && file_exists($credsPath)) {
                        @unlink($credsPath);
                        $credsDeleted = true;
                    }
                    break;

                case 'nav_complete':
                    $lastUrl = $event['url'] ?? $lastUrl;
                    $pagesCrawled++;
                    break;

                case 'finding':
                    $this->persistFinding($run, $event, $job);
                    break;

                case 'progress':
                    $this->line(sprintf(
                        '    [%s/%s] %d pages, %d findings so far',
                        $job['tenant_slug'], $job['role'],
                        $event['pages_crawled'] ?? 0,
                        $event['findings_so_far'] ?? 0,
                    ));
                    break;

                case 'timeout':
                    $this->warn(sprintf(
                        '    [%s/%s] page timeout at: %s (step: %s)',
                        $job['tenant_slug'], $job['role'],
                        $event['at_url'] ?? '?', $event['at_step'] ?? '?'
                    ));
                    break;

                case 'error':
                    $this->warn(sprintf(
                        '    [%s/%s] Node error: %s',
                        $job['tenant_slug'], $job['role'],
                        $event['message'] ?? 'unknown'
                    ));
                    break;

                case 'done':
                    if (isset($event['pages_crawled']) && is_int($event['pages_crawled'])) {
                        $pagesCrawled = $event['pages_crawled']; // trust Node's authoritative count
                    }
                    break;
            }
        };

        // ── Polling loop ────────────────────────────────────────────────────
        while (! $process->isTerminated()) {
            try {
                $process->checkTimeout(); // throws ProcessTimedOutException
            } catch (ProcessTimedOutException) {
                $process->stop(3); // SIGTERM, 3s grace period, then SIGKILL
                $run->update(['last_url_crawled' => $lastUrl, 'last_role_crawled' => $job['role']]);
                return 'timeout';
            }

            if (now()->gt($deadline)) {
                $process->stop(3);
                $run->update(['last_url_crawled' => $lastUrl, 'last_role_crawled' => $job['role']]);
                return 'timeout';
            }

            $chunk = $process->getIncrementalOutput();
            if ($chunk !== '') {
                $buffer .= $chunk;
                $lines   = explode("\n", $buffer);
                $buffer  = array_pop($lines); // keep last (possibly partial) line

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $event = json_decode($line, true);
                    if (is_array($event)) {
                        $dispatch($event);
                    } else {
                        $this->warn("    [Node stdout unparseable] {$line}");
                    }
                }
            }

            usleep(100_000); // 100ms poll — pages take 2-5s so this is fine
        }

        // ── Flush remaining buffer after process exits ──────────────────────
        // Process whatever remains in buffer. If it doesn't end in newline,
        // the last segment may be incomplete JSON — json_decode returns null
        // and we silently drop it. Node should always end with a done/error
        // event followed by newline, so an incomplete tail means Node crashed
        // mid-write and the buffer's last bytes are unrecoverable noise.
        $remaining = $process->getIncrementalOutput();
        $buffer   .= $remaining;
        if ($buffer !== '') {
            $lines = explode("\n", $buffer . "\n");
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $event = json_decode($line, true);
                if (is_array($event)) {
                    $dispatch($event);
                }
            }
        }

        // ── Persist total_pages (DB-direct, outside per-finding transactions) ─
        if ($pagesCrawled > 0) {
            DB::connection('central')->table('audit_runs')
                ->where('id', $run->id)
                ->increment('total_pages', $pagesCrawled);
        }

        // ── Detect Node crash (non-zero exit) ───────────────────────────────
        if (! $process->isSuccessful()) {
            $stderr = trim($process->getErrorOutput());
            $this->warn(sprintf(
                '    [%s/%s] Node exited %d%s',
                $job['tenant_slug'], $job['role'],
                $process->getExitCode() ?? -1,
                $stderr !== '' ? ": {$stderr}" : ''
            ));
            return 'error';
        }

        return 'done';
    }

    /**
     * Writes credentials JSON atomically at mode 0600.
     * Uses a .tmp-then-rename pattern: the final path only appears once
     * the file is complete and has correct permissions. No partial-write window.
     *
     * Note: $path is per-run/per-role unique (constructed in runJob() from
     * run_id + tenant_slug + role), so concurrent audit:run invocations would
     * have distinct paths. The PID suffix on the tempfile prevents collision
     * in pathological cases (e.g., same role across overlapping runs in cron).
     */
    private function writeCredsFile(string $path, string $email, string $password): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true); // 0700: only process owner can list dir
        }

        $json    = json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR);
        $tmpPath = $path . '.tmp.' . getmypid();

        file_put_contents($tmpPath, $json, LOCK_EX);
        chmod($tmpPath, 0600);
        rename($tmpPath, $path); // atomic on POSIX — appears complete or not at all
    }

    /**
     * Constructs the Node crawl Process. Password is NOT passed as an argument —
     * Node reads it from the 0600 creds file at --creds-file.
     *
     * Effective timeout = min(--role-timeout, remaining ms to global deadline).
     * Node receives ms; Symfony Process uses seconds.
     */
    private function buildCrawlProcess(
        AuditRun $run,
        array $job,
        string $credsPath,
        \Carbon\Carbon $deadline
    ): Process {
        $screenshotsDir = storage_path("app/audit-screenshots/{$run->id}");
        if (! is_dir($screenshotsDir)) {
            mkdir($screenshotsDir, 0755, true);
        }

        $roleTimeoutMs = (int) $this->option('role-timeout');
        $remainingMs   = (int) max(1000, ($deadline->getTimestamp() - now()->getTimestamp()) * 1000);
        $effectiveMs   = min($roleTimeoutMs, $remainingMs);

        $args = [
            'node',
            storage_path('audit-scripts/crawl.js'),
            '--creds-file='      . $credsPath,
            '--run-id='          . $run->id,
            '--tenant='          . $job['tenant_slug'],
            '--role='            . $job['role'],
            '--panel='           . $job['panel'],
            '--login-url='       . $job['login_url'],
            '--panel-url='       . $job['panel_url'],
            '--screenshots-dir=' . $screenshotsDir,
            '--page-timeout='    . $this->option('page-timeout'),
            '--role-timeout='    . $effectiveMs,
            '--max-urls='        . $this->option('max-urls'),
        ];

        if ($job['single_url']) {
            $args[] = '--single-url=' . $job['single_url'];
        }

        if (! $this->option('no-headless')) {
            $args[] = '--headless';
        }

        $process = new Process($args);
        $process->setTimeout($effectiveMs / 1000); // Symfony uses seconds
        $process->setWorkingDirectory(base_path());

        return $process;
    }

    /**
     * Persists one finding and atomically increments the run's severity counter.
     *
     * Both operations are in a single DB transaction: at any moment,
     * run.findings_{severity} == actual row count for that severity, even if
     * the audit process crashes mid-run.
     *
     * The (run_id, finding_hash) unique constraint silently absorbs any duplicate
     * emissions from Node without raising an exception to the caller.
     */
    private function persistFinding(AuditRun $run, array $event, array $job): void
    {
        $severity    = $event['severity']     ?? 'info';
        $findingType = $event['finding_type'] ?? 'unknown';
        $url         = $event['url']          ?? $job['panel_url'];
        $title       = $event['title']        ?? $findingType;

        $hash = hash('sha256', implode('|', [
            $job['tenant_slug'],
            $job['role'],
            $url,
            $findingType,
            $title,
        ]));

        try {
            DB::connection('central')->transaction(function () use (
                $run, $event, $job, $severity, $hash, $findingType, $url, $title
            ): void {
                AuditFinding::create([
                    'id'              => (string) Str::uuid(),
                    'run_id'          => $run->id,
                    'finding_hash'    => $hash,
                    'tenant_slug'     => $job['tenant_slug'],
                    'role'            => $job['role'],
                    'url'             => $url,
                    'finding_type'    => $findingType,
                    'severity'        => $severity,
                    'title'           => $title,
                    'details'         => $event['details'] ?? [],
                    'screenshot_path' => $event['screenshot'] ?? null,
                    'http_status'     => isset($event['http_status']) ? (int) $event['http_status'] : null,
                    'detected_at'     => now(),
                ]);

                $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];
                if (in_array($severity, $validSeverities, true)) {
                    // DB-direct increment — bypasses model observers, stays in transaction
                    DB::connection('central')->table('audit_runs')
                        ->where('id', $run->id)
                        ->increment("findings_{$severity}");
                } elseif ($severity !== 'ok') {
                    // 'ok' is intentionally not counted (positive assertion findings).
                    // Anything else is a Node script bug that needs investigation.
                    Log::warning('AuditRunCommand: unknown severity from Node', [
                        'severity'     => $severity,
                        'finding_hash' => $hash,
                        'role'         => $job['role'],
                        'tenant'       => $job['tenant_slug'],
                    ]);
                }
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Duplicate (run_id, finding_hash): Node emitted the same finding twice.
            // Already recorded and already counted. Silently absorb.
        }
    }

    /**
     * Derives the demo-seeder password for a school-tenant user.
     * Mirrors Pak::demoPassword() in database/seeders/Demo/Support/Pak.php.
     *
     * Uses config('app.key') — NOT env('APP_KEY') — to survive config:cache.
     * The return value must never be logged, stored, or passed as a process arg.
     */
    private function derivePassword(string $roleKey, string $email): string
    {
        return 'Demo2026@' . substr(sha1($roleKey . $email . config('app.key')), 0, 6);
    }

    // ── Group 4: Run finalisation + reporting ────────────────────────────

    private function resolveScope(): string
    {
        if ($this->option('url')) {
            return 'url';
        }
        if ($this->option('role')) {
            return 'role';
        }
        if ($this->option('tenant')) {
            return 'tenant';
        }
        return 'all';
    }

    /**
     * Checkpoint: update last_url_crawled / last_role_crawled on the run record
     * after every role batch so a crash leaves a breadcrumb.
     * Uses job field names: 'single_url' (set for --url runs) or 'panel_url'.
     */
    private function flushRunRecord(AuditRun $run, string $status, array $lastJob): void
    {
        $run->update([
            'status'            => $status,
            'last_url_crawled'  => $lastJob['single_url'] ?? $lastJob['panel_url'] ?? null,
            'last_role_crawled' => $lastJob['role'] ?? null,
        ]);
    }

    /**
     * Close the run: set status + finished_at, write JSON report to storage.
     * refresh() is called first to pick up counter increments written by
     * persistFinding() transactions during the run.
     */
    private function finaliseRun(AuditRun $run, string $status): void
    {
        $run->refresh(); // pick up counter increments from persistFinding() transactions

        $reportDir  = storage_path('audit-reports');
        $reportFile = $reportDir . '/' . $run->id . '.json';

        if (! is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $findings = AuditFinding::where('run_id', $run->id)
            ->orderBy('severity')
            ->orderBy('detected_at')
            ->get()
            ->toArray();

        $report = [
            'run'      => $run->toArray(),
            'findings' => $findings,
        ];

        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $run->update([
            'status'           => $status,
            'finished_at'      => now(),
            'json_report_path' => $reportFile,
        ]);
    }

    /**
     * Print the §10 summary table to the terminal.
     * Called with $run->fresh() so counter columns are current.
     */
    private function printSummary(AuditRun $run, array $saasMeta): void
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line(sprintf(
            '  Audit run  %s  [%s]',
            $run->id,
            strtoupper($run->status),
        ));
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table(
            ['Severity', 'Count'],
            [
                ['CRITICAL', $run->findings_critical],
                ['HIGH',     $run->findings_high],
                ['MEDIUM',   $run->findings_medium],
                ['LOW',      $run->findings_low],
                ['INFO',     $run->findings_info],
            ],
        );

        $this->line(sprintf('  Pages crawled : %d', $run->total_pages));
        $this->line(sprintf('  Duration      : %ds',
            abs($run->finished_at
                ? $run->finished_at->diffInSeconds($run->started_at)
                : now()->diffInSeconds($run->started_at))
        ));

        if ($run->saas_skipped) {
            $this->warn(sprintf(
                '  SaaS panel skipped (%s)', $run->saas_skip_reason ?? 'unknown',
            ));
        }

        if (! empty($this->skippedRoles)) {
            $this->warn('  Roles skipped (no demo user found):');
            foreach ($this->skippedRoles as $entry) {
                $this->warn(sprintf('    • %s / %s', $entry['tenant'], $entry['role']));
            }
        }

        if ($run->json_report_path) {
            $this->line(sprintf('  Report        : %s', $run->json_report_path));
        }

        $this->newLine();
    }
}
