<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * CheckEnvironment — Pre-deploy environment validation.
 *
 * Usage:
 *   php artisan kynex:check-env
 *
 * Exits with code 0 when all required variables are set and no known
 * placeholder/weak values are detected. Exits with code 1 if any
 * hard failures are found.
 *
 * Wire this into your CI/CD pipeline before every production deploy.
 */
class CheckEnvironment extends Command
{
    protected $signature = 'kynex:check-env
                            {--strict : Treat warnings as failures (exit 1 on warnings too)}';

    protected $description = 'Validate that all required environment variables are set and contain no placeholder values';

    // ── Variable Definitions ───────────────────────────────────────

    /**
     * Variables that MUST be set for the application to function at all.
     * Missing any of these is a hard failure → exit 1.
     */
    private const REQUIRED = [
        'APP_KEY'              => 'Laravel encryption key (run: php artisan key:generate)',
        'APP_URL'              => 'Application URL',
        'DB_HOST'              => 'Database host',
        'DB_DATABASE'          => 'Database name',
        'DB_USERNAME'          => 'Database username',
        'DB_PASSWORD'          => 'Database password',
        'TENANCY_DB_USERNAME'  => 'Tenant database username',
        'TENANCY_DB_PASSWORD'  => 'Tenant database password',
        'RESEND_API_KEY'       => 'Resend email API key (get from resend.com/api-keys)',
        'SAAS_ADMIN_EMAIL'     => 'Super admin email address',
        'SAAS_ADMIN_PASSWORD'  => 'Super admin password',
    ];

    /**
     * Variables required only when their feature flag is enabled.
     * Each entry: [ 'VAR_NAME' => ['guard_var' => 'FEATURE_FLAG', 'hint' => '...'] ]
     */
    private const CONDITIONAL = [
        'OPENROUTER_API_KEY' => [
            'guard_var' => 'AI_ENABLED',
            'hint'      => 'OpenRouter API key (get from openrouter.ai/keys)',
        ],
        'FCM_SERVER_KEY' => [
            'guard_var' => null, // Always warn if still a placeholder
            'hint'      => 'Firebase Cloud Messaging server key (Firebase Console → Cloud Messaging)',
        ],
        'FCM_PROJECT_ID' => [
            'guard_var' => null,
            'hint'      => 'Firebase project ID',
        ],
        'JAZZCASH_MERCHANT_ID' => [
            'guard_var' => 'JAZZCASH_ENABLED',
            'hint'      => 'JazzCash merchant ID (sandbox.jazzcash.com.pk)',
        ],
        'JAZZCASH_PASSWORD' => [
            'guard_var' => 'JAZZCASH_ENABLED',
            'hint'      => 'JazzCash merchant password',
        ],
        'JAZZCASH_INTEGRITY_SALT' => [
            'guard_var' => 'JAZZCASH_ENABLED',
            'hint'      => 'JazzCash integrity salt / hash key',
        ],
        'EASYPAISA_STORE_ID' => [
            'guard_var' => 'EASYPAISA_ENABLED',
            'hint'      => 'EasyPaisa store ID (easypay.easypaisa.com.pk)',
        ],
        'EASYPAISA_HASH_KEY' => [
            'guard_var' => 'EASYPAISA_ENABLED',
            'hint'      => 'EasyPaisa hash key',
        ],
    ];

    /**
     * Patterns that indicate a placeholder value was left unchanged.
     * Any env var whose value matches one of these triggers a warning.
     */
    private const PLACEHOLDER_PATTERNS = [
        '/^your[-_]/i',
        '/[-_]here$/i',
        '/xxxx/i',
        '/change[-_]me/i',
        '/example\.com/i',
        '/sk-or-v1-xxx/i',
        '/re_xxx/i',
        '/your-firebase/i',
        '/your-evolution/i',
    ];

    /**
     * Known-weak values that should never be used in production.
     * Exact-match check (lowercased).
     */
    private const WEAK_VALUES = [
        'password',
        'secret',
        '12345678',
        'changeme',
        'change_me',
        'admin',
        'test',
        'kynexedu123',
    ];

    // ── Run ─────────────────────────────────────────────────────────

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=cyan;options=bold>KynexEdu Environment Check</>');
        $this->line('  ' . str_repeat('─', 50));
        $this->newLine();

        $failures = 0;
        $warnings = 0;
        $passes   = 0;
        $strict   = (bool) $this->option('strict');
        $isProd   = app()->environment('production');

        // ── 1. Check required variables ──────────────────────────
        $this->line('  <options=bold>Required Variables</>');

        foreach (self::REQUIRED as $var => $hint) {
            $value = env($var);

            if ($value === null || $value === '') {
                $this->line("  <fg=red>✗</> {$var}");
                $this->line("    <fg=red>MISSING</> — {$hint}");
                $failures++;
                continue;
            }

            $placeholderHit = $this->matchesPlaceholder((string) $value);
            $weakHit        = $this->isWeakValue((string) $value);

            if ($placeholderHit) {
                $this->line("  <fg=yellow>!</> {$var}");
                $this->line("    <fg=yellow>PLACEHOLDER</> value detected — replace with a real value");
                $warnings++;
            } elseif ($weakHit) {
                $this->line("  <fg=yellow>!</> {$var}");
                $this->line("    <fg=yellow>WEAK VALUE</> — use a strong, unique value");
                $warnings++;
            } else {
                $this->line("  <fg=green>✓</> {$var}");
                $passes++;
            }
        }

        $this->newLine();

        // ── 2. Check conditional variables ────────────────────────
        $this->line('  <options=bold>Feature-Conditional Variables</>');

        foreach (self::CONDITIONAL as $var => $meta) {
            $guardVar   = $meta['guard_var'];
            $hint       = $meta['hint'];
            $isEnabled  = $guardVar ? filter_var(env($guardVar, false), FILTER_VALIDATE_BOOLEAN) : true;

            if (! $isEnabled) {
                $this->line("  <fg=gray>–</> {$var} (feature disabled — skipped)");
                continue;
            }

            $value = env($var);

            if ($value === null || $value === '') {
                $this->line("  <fg=yellow>!</> {$var}");
                $this->line("    <fg=yellow>MISSING</> — {$hint}");
                $warnings++;
                continue;
            }

            if ($this->matchesPlaceholder((string) $value)) {
                $this->line("  <fg=yellow>!</> {$var}");
                $this->line("    <fg=yellow>PLACEHOLDER</> value — replace with a real key");
                $warnings++;
            } else {
                $this->line("  <fg=green>✓</> {$var}");
                $passes++;
            }
        }

        $this->newLine();

        // ── 3. Production-only checks ─────────────────────────────
        $this->line('  <options=bold>Production Safety</>');

        if ($isProd && filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->line('  <fg=red>✗</> APP_DEBUG=true in production — MUST be false');
            $failures++;
        } else {
            $this->line('  <fg=green>✓</> APP_DEBUG');
            $passes++;
        }

        $logLevel = strtolower((string) env('LOG_LEVEL', 'debug'));
        if ($isProd && in_array($logLevel, ['debug', 'info'], true)) {
            $this->line("  <fg=yellow>!</> LOG_LEVEL={$logLevel} in production — consider 'warning' or 'error'");
            $warnings++;
        } else {
            $this->line("  <fg=green>✓</> LOG_LEVEL={$logLevel}");
            $passes++;
        }

        $this->newLine();

        // ── 4. Summary ────────────────────────────────────────────
        $this->line('  ' . str_repeat('─', 50));
        $this->line("  <fg=green>✓ {$passes} passed</>   <fg=yellow>! {$warnings} warnings</>   <fg=red>✗ {$failures} failures</>");
        $this->newLine();

        if ($failures > 0) {
            $this->line('  <fg=red;options=bold>❌  Environment check FAILED — fix all failures before deploying.</>');
            $this->newLine();
            return self::FAILURE;
        }

        if ($warnings > 0 && $strict) {
            $this->line('  <fg=yellow;options=bold>⚠️  Warnings found — strict mode treats these as failures.</>');
            $this->newLine();
            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->line('  <fg=yellow;options=bold>⚠️  Warnings found — review before deploying to production.</>');
        } else {
            $this->line('  <fg=green;options=bold>✅  All checks passed.</>');
        }

        $this->newLine();
        return self::SUCCESS;
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function matchesPlaceholder(string $value): bool
    {
        foreach (self::PLACEHOLDER_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private function isWeakValue(string $value): bool
    {
        return in_array(strtolower(trim($value)), self::WEAK_VALUES, true);
    }
}
