<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Database\Seeders\DefaultCertificateAndIdCardTemplatesSeeder;
use App\Database\Seeders\DemoSchoolSeeder;
use App\Database\Seeders\NotificationTemplatesSeeder;
use App\Database\Seeders\TenantDefaultRolesSeeder;
use App\Enums\TenantStatus;
use App\Models\SchoolUser;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnsureDevDemoTenant extends Command
{
    protected $signature = 'kynex:ensure-dev-demo';

    protected $description = 'Ensure the local development demo tenant and school login exist';

    public function handle(): int
    {
        // Don't run in production unless explicitly opted in. The command
        // exists for local dev convenience; on a SaaS production server
        // every tenant should come through the SaaS approval flow, not
        // be re-seeded on every container boot.
        if (app()->environment('production') && ! filter_var(env('ENABLE_DEMO_TENANT_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('kynex:ensure-dev-demo skipped (production environment).');
            return self::SUCCESS;
        }

        $tenantId = env('DEMO_TENANT_ID', 'demo');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $tenant = Tenant::create([
                'id' => $tenantId,
                'status' => TenantStatus::Trial,
                'school_name' => 'Demo School',
                'admin_name' => 'School Administrator',
                'admin_email' => 'admin@demo.kynexedu.com',
                'trial_ends_at' => Carbon::now()->addYears(5),
                'active_student_count' => 0,
                'storage_used_mb' => 0,
                'whatsapp_channel' => 'none',
                'sms_channel' => 'none',
                'ai_enabled' => false,
                'ai_model' => 'openai/gpt-4o-mini',
                'ai_monthly_budget_paisas' => 0,
                'ai_used_this_month_paisas' => 0,
                'preferred_language' => 'en',
            ]);

            $this->info("Created demo tenant [{$tenant->id}].");
        } else {
            $tenant->update([
                'school_name' => $tenant->school_name ?: 'Demo School',
                'admin_name' => $tenant->admin_name ?: 'School Administrator',
                'admin_email' => $tenant->admin_email ?: 'admin@demo.kynexedu.com',
                'status' => $tenant->status ?: TenantStatus::Trial,
            ]);

            $this->info("Demo tenant [{$tenant->id}] already exists.");
        }

        $dbName = $tenant->database()->getName();
        if (! $tenant->database()->manager()->databaseExists($dbName)) {
            $tenant->database()->manager()->createDatabase($tenant);
            $this->info("Created tenant database [{$dbName}].");
        }

        $this->info('Preparing demo tenant database...');

        $this->call('tenants:migrate', [
            '--tenants' => [$tenantId],
        ]);

        $tenant->run(function () {
            (new TenantDefaultRolesSeeder())->setCommand($this)->run();
            (new NotificationTemplatesSeeder())->run();
            (new DefaultCertificateAndIdCardTemplatesSeeder())->run();

            $demoAdmin = SchoolUser::where('email', 'admin@demo.kynexedu.com')->first();

            if (! $demoAdmin) {
                (new DemoSchoolSeeder())->setCommand($this)->run();
                $this->info('Seeded demo school users for /login.');
            } else {
                $demoAdmin->forceFill([
                    'name' => 'School Administrator',
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ])->save();

                if (! $demoAdmin->hasRole('SCHOOL_ADMIN')) {
                    $demoAdmin->assignRole('SCHOOL_ADMIN');
                }

                $this->info('Demo school admin login refreshed for /login.');
            }
        });

        $this->newLine();
        $this->table(['Portal', 'Email', 'Password'], [
            ['/saas/login', env('SAAS_ADMIN_EMAIL', 'admin@kynexedu.com'), env('SAAS_ADMIN_PASSWORD', 'password')],
            ['/login', 'admin@demo.kynexedu.com', 'password'],
        ]);

        return self::SUCCESS;
    }
}
