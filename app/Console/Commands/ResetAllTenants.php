<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NUCLEAR option for staging / pre-launch reset:
 *  - Drops every tenant database that exists
 *  - Deletes every row from the central tenants/domains/invoices/
 *    approval_requests/school_invitations/ai_usage_logs tables
 *
 * After this runs, the SaaS admin must re-onboard schools from scratch.
 *
 *   php artisan kynex:reset-all-tenants --dry-run
 *   php artisan kynex:reset-all-tenants --force
 */
class ResetAllTenants extends Command
{
    protected $signature = 'kynex:reset-all-tenants
                            {--dry-run : Show planned destruction without writing}
                            {--force : Skip the y/N prompt}';

    protected $description = 'DESTROY every tenant database and central tenancy row. Use only on staging or before launch.';

    public function handle(): int
    {
        $tenants = Tenant::query()->get();

        $this->warn('This will DESTROY every tenant database and central tenancy row.');
        $this->line('  - ' . $tenants->count() . ' tenants will be dropped');
        foreach ($tenants as $t) {
            $this->line("    · {$t->id} ({$t->school_name})");
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm('Type yes to nuke everything. Are you sure?', false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        // 1. Drop tenant databases.
        foreach ($tenants as $tenant) {
            $dbName = method_exists($tenant, 'database') && method_exists($tenant->database(), 'getName')
                ? $tenant->database()->getName()
                : 'tenant' . $tenant->id;

            $exists = (bool) DB::connection('central')
                ->select("SELECT 1 FROM pg_database WHERE datname = ?", [$dbName]);

            if (! $exists) {
                $this->line("  [skip] DB {$dbName} does not exist");
                continue;
            }

            $this->line("  Dropping DB: {$dbName}");
            if (! $dryRun) {
                try {
                    // Force-disconnect any sessions using the DB before dropping.
                    DB::connection('central')->statement(
                        "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?",
                        [$dbName]
                    );
                    DB::connection('central')->statement('DROP DATABASE IF EXISTS "' . $dbName . '"');
                    $this->info("    ✓ dropped");
                } catch (\Throwable $e) {
                    $this->error("    ✗ failed: {$e->getMessage()}");
                }
            }
        }

        // 2. Wipe central tenancy rows.
        $tablesToTruncate = [
            'school_invitations',
            'invoices',
            'ai_usage_logs',
            'approval_requests',
            'tenant_signups',
            'domains',
            'tenants',
        ];

        foreach ($tablesToTruncate as $table) {
            $exists = Schema::connection('central')->hasTable($table);
            if (! $exists) {
                continue;
            }
            $count = DB::connection('central')->table($table)->count();
            $this->line("  Central table {$table}: {$count} rows");
            if (! $dryRun && $count > 0) {
                DB::connection('central')->statement('TRUNCATE TABLE "' . $table . '" RESTART IDENTITY CASCADE');
                $this->info("    ✓ truncated");
            }
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run complete. No changes written.' : 'Reset complete. Ready for fresh tenant onboarding from /saas.');
        return self::SUCCESS;
    }
}
