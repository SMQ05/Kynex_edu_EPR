<?php

declare(strict_types=1);

namespace App\Console\Commands;

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

        $tenant->run(function () {
            $this->info('Preparing demo tenant database...');

            $this->callSilent('migrate', [
                '--path' => database_path('migrations/tenant'),
                '--force' => true,
            ]);

            (new TenantDefaultRolesSeeder())->setCommand($this)->run();
            (new NotificationTemplatesSeeder())->run();

            $demoAdminExists = SchoolUser::where('email', 'admin@demo.kynexedu.com')->exists();

            if (! $demoAdminExists) {
                (new DemoSchoolSeeder())->setCommand($this)->run();
                $this->info('Seeded demo school users for /login.');
            } else {
                $this->info('Demo school users already exist. Skipping reseed.');
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
