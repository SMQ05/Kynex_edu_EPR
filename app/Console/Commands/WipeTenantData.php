<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Database\Seeders\DefaultCertificateAndIdCardTemplatesSeeder;
use App\Database\Seeders\NotificationTemplatesSeeder;
use App\Database\Seeders\TenantDefaultRolesSeeder;
use App\Models\SchoolUser;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Truncates all user-created data inside each tenant DB while preserving:
 *  - role / permission rows (so RBAC works)
 *  - certificate_templates and id_card_templates (so they don't have to
 *    be reseeded by hand)
 *  - notification_templates
 *  - the migrations table
 *  - the SchoolUser whose email matches tenants.admin_email (so the
 *    school can still log in after the wipe)
 *
 * Use --dry-run first to preview the plan.
 *
 *   php artisan kynex:wipe-tenant-data --dry-run
 *   php artisan kynex:wipe-tenant-data demo
 *   php artisan kynex:wipe-tenant-data
 */
class WipeTenantData extends Command
{
    protected $signature = 'kynex:wipe-tenant-data
                            {tenant? : Optional tenant id}
                            {--dry-run : Show planned wipe without writing}
                            {--force : Skip the y/N prompt (for scripts)}';

    protected $description = 'Truncate tenant test data while preserving roles, templates, and the primary admin user';

    /**
     * Tables we never touch — they hold seeded data that's part of the
     * application contract, not user-added test data.
     */
    protected array $protectedTables = [
        'migrations',
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'certificate_templates',
        'id_card_templates',
        'notification_templates',
        // school_users is handled specially below — we keep just the primary admin
    ];

    public function handle(): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->get()
            : Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenants.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            $this->warn('This will DELETE all student / staff / class / exam / homework data across:');
            foreach ($tenants as $t) {
                $this->line("  - {$t->id} (admin: {$t->admin_email})");
            }
            if (! $this->confirm('Are you sure?', false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->line("=== Tenant: {$tenant->id} ===");

            try {
                $tenant->run(function () use ($tenant, $dryRun) {
                    $this->wipeCurrentTenant($tenant, $dryRun);
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    protected function wipeCurrentTenant(Tenant $tenant, bool $dryRun): void
    {
        // 1. Find primary admin BEFORE wiping anything, so we know whose row to keep.
        $adminUser = SchoolUser::where('email', $tenant->admin_email)->first();
        $adminId = $adminUser?->id;

        if ($adminUser) {
            $this->line("  Primary admin: {$adminUser->email} (will be preserved)");
        } else {
            $this->warn("  ⚠ No SchoolUser found for {$tenant->admin_email}; will recreate from seeders.");
        }

        // 2. Discover every table in this tenant's schema.
        $allTables = collect(DB::select("
            SELECT tablename FROM pg_tables WHERE schemaname = current_schema()
        "))->pluck('tablename')->all();

        $protected = array_merge($this->protectedTables, ['school_users']);
        $wipeable = array_diff($allTables, $protected);

        // 3. Order doesn't matter with TRUNCATE ... CASCADE.
        $rowCounts = [];
        foreach ($wipeable as $table) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                $rowCounts[$table] = $count;
            }
        }

        if (empty($rowCounts)) {
            $this->line('  No data to wipe.');
        } else {
            $this->line(sprintf('  Will truncate %d table(s):', count($rowCounts)));
            foreach ($rowCounts as $table => $count) {
                $this->line(sprintf('     %-40s %s rows', $table, $count));
            }

            if (! $dryRun) {
                $list = implode(', ', array_map(fn ($t) => '"' . $t . '"', array_keys($rowCounts)));
                DB::statement("TRUNCATE TABLE {$list} RESTART IDENTITY CASCADE");
                $this->info('  ✓ Tables truncated');
            } else {
                $this->line('  [dry-run] would TRUNCATE ... CASCADE');
            }
        }

        // 4. Drop non-admin school_users.
        $nonAdminCount = SchoolUser::query()
            ->when($adminId, fn ($q) => $q->where('id', '!=', $adminId))
            ->count();

        if ($nonAdminCount > 0) {
            $this->line("  Will delete {$nonAdminCount} non-admin school_users");
            if (! $dryRun) {
                SchoolUser::query()
                    ->when($adminId, fn ($q) => $q->where('id', '!=', $adminId))
                    ->forceDelete();
                $this->info("  ✓ Non-admin school_users deleted");
            }
        } else {
            $this->line('  No non-admin school_users to delete');
        }

        // 5. If admin user is missing entirely, recreate stub via the
        //    role seeder + tenant data.
        if ($dryRun) {
            $this->line('  [dry-run] would re-seed roles + templates if missing');
            return;
        }

        // 6. Re-seed roles, notification templates, certificate/id card
        //    templates. firstOrCreate-style seeders are idempotent.
        (new TenantDefaultRolesSeeder())->run();
        (new NotificationTemplatesSeeder())->run();
        (new DefaultCertificateAndIdCardTemplatesSeeder())->run();
        $this->info('  ✓ Re-seeded roles, notification templates, certificate templates');

        // 7. Make sure the primary admin still has SCHOOL_ADMIN role + is active.
        if ($adminUser) {
            $adminUser = SchoolUser::find($adminUser->id);
            if ($adminUser && ! $adminUser->hasRole('SCHOOL_ADMIN')) {
                $adminUser->assignRole('SCHOOL_ADMIN');
            }
            $adminUser?->forceFill([
                'is_active'   => true,
                'active_role' => 'SCHOOL_ADMIN',
                'campus_id'   => null,
            ])->saveQuietly();
            $this->info('  ✓ Primary admin re-activated as SCHOOL_ADMIN');
        }
    }
}
