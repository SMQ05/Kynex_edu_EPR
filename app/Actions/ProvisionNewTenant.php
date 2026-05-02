<?php

declare(strict_types=1);

namespace App\Actions;

use App\Database\Seeders\DefaultCertificateAndIdCardTemplatesSeeder;
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
use Resend\Client as ResendClient;

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
                '--path'  => 'database/migrations/tenant',
                '--force' => true,
            ]);

            (new TenantDefaultRolesSeeder())->run();
            (new NotificationTemplatesSeeder())->run();
            (new DefaultCertificateAndIdCardTemplatesSeeder())->run();

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
        $this->doProvision($tenant, password: $password);
    }

    /**
     * Same as invokeWithPassword but accepts a pre-hashed bcrypt string.
     * Used by the SaaS approve-and-provision flow where the applicant
     * already set their password before SaaS approval.
     */
    public function invokeWithHashedPassword(Tenant $tenant, string $hashedPassword): void
    {
        $this->doProvision($tenant, hashedPassword: $hashedPassword);
    }

    private function doProvision(Tenant $tenant, ?string $password = null, ?string $hashedPassword = null): void
    {
        Log::info('Provisioning tenant', ['tenant_id' => $tenant->id]);

        tenancy()->initialize($tenant);

        try {
            Artisan::call('migrate', [
                '--path'  => 'database/migrations/tenant',
                '--force' => true,
            ]);

            (new TenantDefaultRolesSeeder())->run();
            (new NotificationTemplatesSeeder())->run();
            (new DefaultCertificateAndIdCardTemplatesSeeder())->run();

            $admin = new SchoolUser();
            $admin->forceFill([
                'name'              => $tenant->admin_name,
                'email'             => $tenant->admin_email,
                'password'          => $password ?: Str::random(40), // placeholder; overwritten below if hashed
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
            $admin->save();

            // The 'password' cast (=> 'hashed') would re-hash an
            // already-hashed string. Bypass the cast by writing the row
            // directly via the query builder.
            if ($hashedPassword) {
                \Illuminate\Support\Facades\DB::table('school_users')
                    ->where('id', $admin->id)
                    ->update(['password' => $hashedPassword]);
            }

            $admin->assignRole('SCHOOL_ADMIN');
            $admin->update(['active_role' => 'SCHOOL_ADMIN']);

            Log::info('School admin created during provision', [
                'tenant_id' => $tenant->id,
                'email'     => $tenant->admin_email,
            ]);

        } finally {
            tenancy()->end();
        }

        $this->sendWelcomeEmail($tenant);

        $tenant->updateQuietly(['active_student_count' => 0]);

        Log::info('Tenant provisioning complete', ['tenant_id' => $tenant->id]);
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
            app(ResendClient::class)->emails->send([
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
            app(ResendClient::class)->emails->send([
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
