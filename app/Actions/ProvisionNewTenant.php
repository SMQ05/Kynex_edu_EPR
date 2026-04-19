<?php

declare(strict_types=1);

namespace App\Actions;

use App\Database\Seeders\NotificationTemplatesSeeder;
use App\Database\Seeders\TenantDefaultRolesSeeder;
use App\Models\SchoolInvitation;
use App\Models\SchoolUser;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Laravel\Facades\Resend;

/**
 * ProvisionNewTenant — Sets up a new tenant's database, creates the
 * admin user, and sends a welcome email.
 *
 * Hooked into TenantResource::afterCreate().
 */
class ProvisionNewTenant
{
    /**
     * Provision a newly created tenant.
     *
     * Called from SaaS admin panel (admin creates tenant).
     * Sends a "set your password" invitation link to the school admin.
     *
     * Steps:
     *  1. Initialize tenancy context
     *  2. Run tenant migrations
     *  3. Seed default roles & permissions
     *  4. Create school admin SchoolUser (inactive, no password yet)
     *  5. End tenancy context
     *  6. Send set-password invitation email via Resend
     *  7. Update tenant metadata
     */
    public function __invoke(Tenant $tenant): void
    {
        Log::info('Provisioning new tenant (admin-created)', ['tenant_id' => $tenant->id]);

        tenancy()->initialize($tenant);

        try {
            Artisan::call('migrate', [
                '--path'  => database_path('migrations/tenant'),
                '--force' => true,
            ]);

            (new TenantDefaultRolesSeeder())->run();
            (new NotificationTemplatesSeeder())->run();

            // Create school admin without a password — they will set it via invite link
            $schoolAdmin = SchoolUser::create([
                'name'      => $tenant->admin_name,
                'email'     => $tenant->admin_email,
                'password'  => Hash::make(Str::random(32)), // unusable placeholder
                'is_active' => false, // inactive until password is set
            ]);
            $schoolAdmin->assignRole('SCHOOL_ADMIN');

            Log::info('School admin placeholder created', [
                'tenant_id' => $tenant->id,
                'email'     => $tenant->admin_email,
            ]);

        } finally {
            tenancy()->end();
        }

        // Send set-password invitation email
        $this->sendAdminInviteEmail($tenant);

        $tenant->updateQuietly(['active_student_count' => 0]);

        Log::info('Tenant provisioning complete', ['tenant_id' => $tenant->id]);
    }

    /**
     * Provision from a self-registration with a known password.
     *
     * Called from SchoolPortalController::provisionFromInvitation().
     */
    public function invokeWithPassword(Tenant $tenant, string $password): void
    {
        Log::info('Provisioning tenant from self-signup', ['tenant_id' => $tenant->id]);

        tenancy()->initialize($tenant);

        try {
            Artisan::call('migrate', [
                '--path'  => database_path('migrations/tenant'),
                '--force' => true,
            ]);

            (new TenantDefaultRolesSeeder())->run();
            (new NotificationTemplatesSeeder())->run();

            $schoolAdmin = SchoolUser::create([
                'name'               => $tenant->admin_name,
                'email'              => $tenant->admin_email,
                'password'           => $password,
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]);
            $schoolAdmin->assignRole('SCHOOL_ADMIN');

            Log::info('School admin created from self-signup', [
                'tenant_id' => $tenant->id,
                'email'     => $tenant->admin_email,
            ]);

        } finally {
            tenancy()->end();
        }

        $this->sendWelcomeEmail($tenant);

        $tenant->updateQuietly(['active_student_count' => 0]);

        Log::info('Self-signup tenant provisioning complete', ['tenant_id' => $tenant->id]);
    }

    /**
     * Send "set your password" invitation email to admin-created school admin.
     */
    private function sendAdminInviteEmail(Tenant $tenant): void
    {
        // Generate a set-password invitation token (3-hour expiry)
        $token      = Str::random(64);
        SchoolInvitation::create([
            'school_name'        => $tenant->school_name,
            'contact_name'       => $tenant->admin_name,
            'email'              => $tenant->admin_email,
            'type'               => 'admin_invite',
            'token'              => $token,
            'expires_at'         => Carbon::now()->addHours(3),
            'email_verified_at'  => now(), // already verified by admin
            'tenant_id'          => $tenant->id,
        ]);

        $setPasswordUrl = route('school.set-password', ['token' => $token]);

        try {
            Resend::emails()->send([
                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                'to'      => $tenant->admin_email,
                'subject' => "Your KynexEdu school is ready — set your password",
                'html'    => view('emails.school-admin-invite', [
                    'tenant'         => $tenant,
                    'setPasswordUrl' => $setPasswordUrl,
                    'expiresAt'      => Carbon::now()->addHours(3)->format('d M Y, h:i A'),
                ])->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send admin invite email', [
                'tenant_id' => $tenant->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send welcome email after self-signup provisioning.
     */
    private function sendWelcomeEmail(Tenant $tenant): void
    {
        $loginUrl = route('school.login');

        try {
            Resend::emails()->send([
                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                'to'      => $tenant->admin_email,
                'subject' => "Welcome to KynexEdu — {$tenant->school_name}",
                'html'    => view('emails.school-welcome', [
                    'tenant'   => $tenant,
                    'loginUrl' => $loginUrl,
                ])->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email', [
                'tenant_id' => $tenant->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

}
