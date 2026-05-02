<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Database\Seeders\DemoSchoolSeeder;
use App\Database\Seeders\NotificationTemplatesSeeder;
use App\Database\Seeders\TenantDefaultRolesSeeder;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * ResetDemoTenant — Wipes and re-seeds a demo tenant's database.
 *
 * Usage:
 *   php artisan kynex:reset-demo                   # resets default "demo" tenant
 *   php artisan kynex:reset-demo --tenant=demo2     # resets a specific tenant
 *   php artisan kynex:reset-demo --fresh            # drops all tables first
 */
class ResetDemoTenant extends Command
{
    protected $signature = 'kynex:reset-demo
        {--tenant=demo : Tenant ID to reset}
        {--fresh : Drop all tables and re-migrate before seeding}
        {--force : Skip confirmation prompt}';

    protected $description = 'Reset a demo tenant database with fresh sample data';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $fresh = $this->option('fresh');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant '{$tenantId}' not found.");
            $this->info('Available tenants:');

            Tenant::all()->each(function (Tenant $t) {
                    $name = $t->data['name'] ?? 'unnamed';
                    $this->line("  - {$t->id} ({$name})");
                });

            return self::FAILURE;
        }

        $this->warn("⚠️  This will ERASE ALL DATA for tenant: {$tenantId}");
        $tenantName = $tenant->data['name'] ?? $tenantId;
        $this->line("   Tenant: {$tenantName}");
        $this->line("   Mode: " . ($fresh ? 'Fresh migration + seed' : 'Truncate + seed'));

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to continue?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $this->info('');
        $this->info("🔄 Resetting tenant: {$tenantId}...");

        $tenant->run(function () use ($fresh) {
            if ($fresh) {
                $this->info('');
                $this->info('📦 Running fresh migration...');
                Artisan::call('migrate:fresh', [
                    '--path'  => 'database/migrations/tenant',
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
            } else {
                $this->info('');
                $this->info('🗑️  Truncating all tenant tables...');
                $this->truncateAllTables();
            }

            // 1. Seed roles & permissions first
            $this->info('');
            $this->info('🔑 Seeding roles & permissions...');
            (new TenantDefaultRolesSeeder())->setCommand($this)->run();

            // 2. Seed notification templates
            $this->info('');
            $this->info('🔔 Seeding notification templates...');
            (new NotificationTemplatesSeeder())->run();

            // 3. Seed demo data
            $this->info('');
            (new DemoSchoolSeeder())->setCommand($this)->run();
        });

        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info("✅ Demo tenant '{$tenantId}' has been reset!");
        $this->info('═══════════════════════════════════════════════');
        $this->info('');
        $this->table(['Role', 'Email', 'Password'], [
            ['Admin', 'admin@demo.kynexedu.com', 'password'],
            ['Teacher', 'teacher1@demo.kynexedu.com', 'password'],
            ['Student', 'student1@demo.kynexedu.com', 'password'],
        ]);

        return self::SUCCESS;
    }

    /**
     * Truncate all tables in the tenant database (except migrations).
     */
    private function truncateAllTables(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            // PostgreSQL: Use TRUNCATE CASCADE for efficiency
            $tables = \Illuminate\Support\Facades\DB::select(
                "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename NOT IN ('migrations', 'telescope_entries', 'telescope_monitoring')"
            );

            if (! empty($tables)) {
                $tableNames = collect($tables)->pluck('tablename')->implode(', ');
                \Illuminate\Support\Facades\DB::statement("TRUNCATE TABLE {$tableNames} CASCADE");
            }
        } elseif ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            $dbName = config("database.connections.{$connection}.database");
            $column = "Tables_in_{$dbName}";

            foreach ($tables as $table) {
                $tableName = $table->$column ?? '';
                if (in_array($tableName, ['migrations', 'telescope_entries', 'telescope_monitoring'])) {
                    continue;
                }
                \Illuminate\Support\Facades\DB::table($tableName)->truncate();
            }

            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('  All tables truncated.');
    }
}
