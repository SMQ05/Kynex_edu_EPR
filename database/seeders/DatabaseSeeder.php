<?php

namespace Database\Seeders;

use App\Enums\SignupStatus;
use App\Models\SaasAdmin;
use App\Models\SubscriptionPlan;
use App\Models\TenantSignup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── 1. SaaS Admin (Superadmin) ──────────────────────────
        $adminEmail = env('SAAS_ADMIN_EMAIL');
        $adminPassword = env('SAAS_ADMIN_PASSWORD');

        if (blank($adminEmail)) {
            throw new \RuntimeException('SAAS_ADMIN_EMAIL is not set in .env — cannot seed super admin.');
        }
        if (blank($adminPassword)) {
            throw new \RuntimeException('SAAS_ADMIN_PASSWORD is not set in .env — cannot seed super admin. Set a strong password first.');
        }

        SaasAdmin::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name'      => 'KynexEdu Admin',
                'password'  => $adminPassword,
                'role'      => 'superadmin',
                'is_active' => true,
            ],
        );

        $this->command->info('✅  SaaS Admin created.');

        // ── 2. Subscription Plans ───────────────────────────────
        // Money stored as paisas (1 PKR = 100 paisas)

        // a) Starter — PKR 150/student/month — max 200 students — core only
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name'                     => 'Starter',
                'description'              => 'Perfect for small schools getting started with digital management.',
                'billing_type'             => 'per_student',
                'base_price_paisas'        => 0,            // no base fee
                'per_student_price_paisas' => 15_000,       // PKR 150 × 100
                'setup_fee_paisas'         => 0,
                'max_students'             => 200,
                'max_staff'                => 30,
                'max_campuses'             => 1,
                'storage_limit_gb'         => 5,
                'enabled_modules'          => [
                    'student_management',
                    'fee_management',
                    'attendance',
                    'timetable',
                ],
                'trial_days'               => 14,
                'is_active'                => false,
                'is_featured'              => false,
                'is_custom'                => false,
                'sort_order'               => 1,
            ],
        );

        // b) Growth — PKR 120/student/month — max 500 students — core + communication + academic
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name'                     => 'Growth',
                'description'              => 'Ideal for growing schools that need communication and academic tools.',
                'billing_type'             => 'per_student',
                'base_price_paisas'        => 0,            // no base fee
                'per_student_price_paisas' => 12_000,       // PKR 120 × 100
                'setup_fee_paisas'         => 0,
                'max_students'             => 500,
                'max_staff'                => 80,
                'max_campuses'             => 3,
                'storage_limit_gb'         => 20,
                'enabled_modules'          => [
                    // core
                    'student_management',
                    'staff_management',
                    'class_management',
                    'fee_management',
                    'attendance',
                    'timetable',
                    // communication
                    'sms_notifications',
                    'email_notifications',
                    'parent_portal',
                    // academic
                    'exam_management',
                    'report_cards',
                    'homework',
                    'online_classes',
                ],
                'trial_days'               => 14,
                'is_active'                => false,
                'is_featured'              => false,
                'is_custom'                => false,
                'sort_order'               => 2,
            ],
        );

        // c) Enterprise — hybrid: base PKR 5,000 + PKR 100/student — unlimited students — all modules
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name'                     => 'Enterprise',
                'description'              => 'Full-featured plan for large institutions with unlimited students.',
                'billing_type'             => 'hybrid',
                'base_price_paisas'        => 500_000,      // PKR 5,000 × 100
                'per_student_price_paisas' => 10_000,       // PKR 100 × 100
                'setup_fee_paisas'         => 0,
                'max_students'             => 0,            // 0 = unlimited
                'max_staff'                => 0,            // 0 = unlimited
                'max_campuses'             => 0,            // 0 = unlimited
                'storage_limit_gb'         => 100,
                'enabled_modules'          => [
                    // core
                    'student_management',
                    'staff_management',
                    'campus_management',
                    'class_management',
                    'fee_management',
                    'attendance',
                    'timetable',
                    // communication
                    'whatsapp_notifications',
                    'sms_notifications',
                    'email_notifications',
                    'parent_portal',
                    // academic
                    'exam_management',
                    'report_cards',
                    'homework',
                    'online_classes',
                    'online_classes_pro',
                    'library',
                    // operations
                    'transport',
                    'hostel',
                    'inventory',
                    'visitor_management',
                    // financial
                    'payroll',
                    'expense_tracking',
                    'budgeting',
                    // advanced
                    'ai_assistant',
                    'advanced_analytics',
                    'custom_reports',
                    'api_access',
                ],
                'trial_days'               => 30,
                'is_active'                => false,
                'is_featured'              => false,
                'is_custom'                => false,
                'sort_order'               => 3,
            ],
        );

        // d) Full Package — PKR 100/student/month — unlimited everything
        //    This is the only plan we currently sell. The 3 above are kept
        //    as inactive references for future tiering.
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'full-package'],
            [
                'name'                     => 'Full Package',
                'description'              => 'All features unlocked. PKR 100 per active student per month. Includes AI assistant, exams, fees, attendance, certificates, hostel, transport, library, parent portal, and unlimited campuses.',
                'billing_type'             => 'per_student',
                'base_price_paisas'        => 0,
                'per_student_price_paisas' => 10_000,        // PKR 100 × 100
                'setup_fee_paisas'         => 0,
                'max_students'             => 0,
                'max_staff'                => 0,
                'max_campuses'             => 0,
                'storage_limit_gb'         => 100,
                'enabled_modules'          => [
                    'attendance', 'fees', 'exams', 'homework', 'library', 'transport',
                    'hostel', 'inventory', 'cafeteria', 'health', 'cms', 'communication',
                    'reports', 'parent_portal', 'student_portal', 'ai_assistant',
                    'certificates', 'id_cards', 'online_classes',
                ],
                'trial_days'               => 14,
                'is_active'                => true,
                'is_featured'              => true,
                'is_custom'                => false,
                'sort_order'               => 0,
            ],
        );

        $this->command->info('✅  Subscription Plans seeded (Full Package active).');

        // Sample TenantSignup rows are intentionally not seeded here
        // anymore — they used to appear after every container boot and
        // polluted the production Signup Leads list.

        // ── 4. RBAC Roles & Permissions (Central Defaults) ──────────
        // NOTE: RbacPermissionSeeder writes to the TENANT database.
        //       It must be run inside a tenant context for each school
        //       (tenancy()->initialize($tenant) before calling the seeder).
        //
        //       To seed a specific tenant manually:
        //         php artisan tenants:artisan "db:seed --class=RbacPermissionSeeder" --tenant=<id>
        //
        //       The seeder is registered here as documentation only.
        //       Uncomment the line below ONLY if running on a single-tenant / test setup.
        //
        // $this->call(RbacPermissionSeeder::class);

        $this->command->info('ℹ️   RbacPermissionSeeder must be run per-tenant. See comment above.');
    }
}
