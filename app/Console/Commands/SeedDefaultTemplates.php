<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Database\Seeders\DefaultCertificateAndIdCardTemplatesSeeder;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Backfill default certificate / ID card templates into existing tenant
 * databases. New tenants get them automatically via ProvisionNewTenant.
 *
 *   php artisan kynex:seed-default-templates           # all active tenants
 *   php artisan kynex:seed-default-templates demo      # one tenant by id
 */
class SeedDefaultTemplates extends Command
{
    protected $signature = 'kynex:seed-default-templates
                            {tenant? : Optional tenant id}
                            {--dry-run : List tenants without seeding}';

    protected $description = 'Seed default certificate and ID card HTML templates into tenant databases';

    public function handle(): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->get()
            : Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenants.');
            return self::FAILURE;
        }

        $this->info(sprintf('Found %d tenant(s).', $tenants->count()));

        if ($this->option('dry-run')) {
            $tenants->each(fn (Tenant $t) => $this->line("  - {$t->id}"));
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->line("Seeding {$tenant->id}...");

            try {
                $tenant->run(function () {
                    (new DefaultCertificateAndIdCardTemplatesSeeder())->run();
                });
                $this->info("  ✓ {$tenant->id}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
