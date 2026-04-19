<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProvisionNewTenant;
use App\Models\SchoolInvitation;
use App\Models\SchoolUser;
use App\Models\Tenant;
use App\Models\TenantSignup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Resend\Laravel\Facades\Resend;

/**
 * SchoolPortalController — Public portal at edu.kynexsolutions.com
 *
 * Handles:
 *  - Landing page
 *  - School self-registration (email verification flow)
 *  - Set-password (self-signup + admin invites)
 *  - Login / Logout
 *  - Forgot / Reset password
 *  - Dashboard selector after login
 */
class SchoolPortalController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    // Landing page
    // ────────────────────────────────────────────────────────────────

    public function landing(): View
    {
        return view('portal.landing');
    }

    // ────────────────────────────────────────────────────────────────
    // Self-registration
    // ────────────────────────────────────────────────────────────────

    public function showRegister(): View
    {
        return view('portal.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name'  => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'required|string|max:30',
            'message'      => 'nullable|string|max:1000',
        ]);

        // Check no pending invitation already exists for this email
        $existing = SchoolInvitation::where('email', $validated['email'])
            ->where('type', 'school_signup')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return back()->with('info', 'A verification link was already sent to this email. Please check your inbox.');
        }

        // Create a CRM TenantSignup record so admin can track it
        TenantSignup::create([
            'school_name'  => $validated['school_name'],
            'contact_name' => $validated['contact_name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'],
            'message'      => $validated['message'] ?? null,
            'status'       => 'new',
        ]);

        // Create invitation with 3-hour expiry
        $token      = Str::random(64);
        $invitation = SchoolInvitation::create([
            'school_name'  => $validated['school_name'],
            'contact_name' => $validated['contact_name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'],
            'type'         => 'school_signup',
            'token'        => $token,
            'expires_at'   => Carbon::now()->addHours(3),
        ]);

        $this->sendVerificationEmail($invitation);

        return redirect()->route('school.register')
            ->with('success', 'We sent a verification link to '.$validated['email'].'. It expires in 3 hours.');
    }

    // ────────────────────────────────────────────────────────────────
    // Email verification
    // ────────────────────────────────────────────────────────────────

    public function verifyEmail(string $token): View|RedirectResponse
    {
        $invitation = SchoolInvitation::where('token', $token)
            ->where('type', 'school_signup')
            ->whereNull('consumed_at')
            ->first();

        if (! $invitation) {
            return view('portal.link-invalid', ['reason' => 'This verification link is invalid or has already been used.']);
        }

        if ($invitation->isExpired()) {
            return view('portal.link-invalid', ['reason' => 'This verification link has expired. Please register again.']);
        }

        // Mark email as verified
        $invitation->update(['email_verified_at' => now()]);

        // Redirect to set-password (same token, now used for password setup)
        return redirect()->route('school.set-password', ['token' => $token])
            ->with('success', 'Email verified! Please set your password to activate your account.');
    }

    // ────────────────────────────────────────────────────────────────
    // Set password (self-signup & admin invites)
    // ────────────────────────────────────────────────────────────────

    public function showSetPassword(string $token): View|RedirectResponse
    {
        $invitation = SchoolInvitation::where('token', $token)
            ->whereNull('consumed_at')
            ->first();

        if (! $invitation) {
            return view('portal.link-invalid', ['reason' => 'This invitation link is invalid or has already been used.']);
        }

        if ($invitation->isExpired()) {
            return view('portal.link-invalid', ['reason' => 'This invitation link has expired. Please contact support or register again.']);
        }

        // For school_signup, email must be verified first
        if ($invitation->type === 'school_signup' && ! $invitation->isEmailVerified()) {
            return view('portal.link-invalid', ['reason' => 'Please verify your email first by clicking the link in your inbox.']);
        }

        return view('portal.set-password', ['invitation' => $invitation, 'token' => $token]);
    }

    public function setPassword(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $invitation = SchoolInvitation::where('token', $token)
            ->whereNull('consumed_at')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return redirect()->route('school.landing')
                ->with('error', 'This link is invalid or has expired.');
        }

        if ($invitation->type === 'school_signup' && ! $invitation->isEmailVerified()) {
            return redirect()->route('school.landing')
                ->with('error', 'Please verify your email first.');
        }

        if ($invitation->type === 'school_signup') {
            // Provision a new tenant for self-registered school
            $this->provisionFromInvitation($invitation, $request->password);
        } else {
            // admin_invite — user already exists, just update their password
            $this->activateInvitedUser($invitation, $request->password);
        }

        // Mark invitation as consumed
        $invitation->update(['consumed_at' => now()]);

        return redirect()->route('school.login')
            ->with('success', 'Your account is ready! Please log in.');
    }

    // ────────────────────────────────────────────────────────────────
    // Login / Logout
    // ────────────────────────────────────────────────────────────────

    public function showLogin(): View
    {
        return view('portal.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // We need to find which tenant this user belongs to
        // Try to auth against school_users guard (tenant-scoped)
        // The tenant context must be set by finding the right tenant DB

        // Find all tenants where admin_email matches
        $tenants = Tenant::where('admin_email', $credentials['email'])->get();

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $user = SchoolUser::where('email', $credentials['email'])->first();

                if ($user && Hash::check($credentials['password'], $user->password) && $user->is_active) {
                    Auth::guard('school_users')->login($user, $request->boolean('remember'));

                    $user->update(['last_login_at' => now()]);

                    // Store tenant ID in session so middleware can initialize tenancy
                    // when accessed via the central domain (e.g. 127.0.0.1 in local dev)
                    $request->session()->put('tenant_id', $tenant->id);

                    tenancy()->end();

                    // Redirect to their school admin panel
                    $domain = $tenant->domains()->first();
                    $adminUrl = $domain
                        ? 'https://'.$domain->domain.'/admin'
                        : config('app.url').'/admin';

                    return redirect()->away($adminUrl);
                }

                tenancy()->end();
            } catch (\Exception $e) {
                // Tenant database does not exist or is unreachable — skip it
                Log::warning('Tenancy initialize failed for tenant during login', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
                tenancy()->end();
            }
        }

        // If not found as admin_email, search all tenants (for non-admin users)
        $allTenants = Tenant::all();
        foreach ($allTenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $user = SchoolUser::where('email', $credentials['email'])->first();

                if ($user && Hash::check($credentials['password'], $user->password) && $user->is_active) {
                    Auth::guard('school_users')->login($user, $request->boolean('remember'));

                    $user->update(['last_login_at' => now()]);

                    // Store tenant ID in session so middleware can initialize tenancy
                    // when accessed via the central domain (e.g. 127.0.0.1 in local dev)
                    $request->session()->put('tenant_id', $tenant->id);

                    tenancy()->end();

                    $domain = $tenant->domains()->first();
                    $adminUrl = $domain
                        ? 'https://'.$domain->domain.'/admin'
                        : config('app.url').'/admin';

                    return redirect()->away($adminUrl);
                }

                tenancy()->end();
            } catch (\Exception $e) {
                // Tenant database does not exist or is unreachable — skip it
                Log::warning('Tenancy initialize failed for tenant during login', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
                tenancy()->end();
            }
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.'])->withInput(['email' => $credentials['email']]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('school_users')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('school.login');
    }

    // ────────────────────────────────────────────────────────────────
    // Forgot / Reset password
    // ────────────────────────────────────────────────────────────────

    public function showForgotPassword(): View
    {
        return view('portal.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;

        // Find the user across all tenants
        $found = false;
        foreach (Tenant::all() as $tenant) {
            tenancy()->initialize($tenant);

            $user = SchoolUser::where('email', $email)->first();

            if ($user) {
                $found = true;

                // Generate reset token (3-hour expiry, reuse SchoolInvitation table)
                SchoolInvitation::where('email', $email)
                    ->where('type', 'password_reset')
                    ->whereNull('consumed_at')
                    ->delete();

                $token = Str::random(64);
                SchoolInvitation::create([
                    'school_name'  => $tenant->school_name,
                    'contact_name' => $user->name,
                    'email'        => $email,
                    'type'         => 'password_reset',
                    'token'        => $token,
                    'expires_at'   => Carbon::now()->addHours(3),
                    'tenant_id'    => $tenant->id,
                    'email_verified_at' => now(), // pre-verified
                ]);

                tenancy()->end();

                $this->sendPasswordResetEmail($user->name, $email, $token);
                break;
            }

            tenancy()->end();
        }

        // Always show success to prevent email enumeration
        return back()->with('success', 'If an account with that email exists, a reset link has been sent.');
    }

    public function showResetPassword(string $token): View|RedirectResponse
    {
        $invitation = SchoolInvitation::where('token', $token)
            ->where('type', 'password_reset')
            ->whereNull('consumed_at')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return view('portal.link-invalid', ['reason' => 'This password reset link is invalid or has expired.']);
        }

        return view('portal.reset-password', ['token' => $token, 'email' => $invitation->email]);
    }

    public function resetPassword(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $invitation = SchoolInvitation::where('token', $token)
            ->where('type', 'password_reset')
            ->whereNull('consumed_at')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return redirect()->route('school.login')
                ->with('error', 'This reset link is invalid or has expired.');
        }

        // Find the tenant and update password
        $tenant = Tenant::find($invitation->tenant_id);
        if ($tenant) {
            tenancy()->initialize($tenant);

            $user = SchoolUser::where('email', $invitation->email)->first();
            if ($user) {
                $user->update(['password' => $request->password]);
            }

            tenancy()->end();
        }

        $invitation->update(['consumed_at' => now()]);

        return redirect()->route('school.login')
            ->with('success', 'Password reset successfully. Please log in.');
    }

    // ────────────────────────────────────────────────────────────────
    // Dashboard (redirects to school panel)
    // ────────────────────────────────────────────────────────────────

    public function dashboard(): RedirectResponse
    {
        return redirect('/admin');
    }

    // ────────────────────────────────────────────────────────────────
    // Private helpers
    // ───────────────────────────────���────────────────────────────────

    private function sendVerificationEmail(SchoolInvitation $invitation): void
    {
        $verifyUrl = route('school.verify-email', ['token' => $invitation->token]);

        try {
            Resend::emails()->send([
                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                'to'      => $invitation->email,
                'subject' => 'Verify your email — KynexEdu School Registration',
                'html'    => view('emails.school-verify-email', [
                    'invitation' => $invitation,
                    'verifyUrl'  => $verifyUrl,
                    'expiresAt'  => $invitation->expires_at->format('d M Y, h:i A'),
                ])->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send school verification email', [
                'email' => $invitation->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendPasswordResetEmail(string $name, string $email, string $token): void
    {
        $resetUrl  = route('school.reset-password', ['token' => $token]);
        $expiresAt = Carbon::now()->addHours(3)->format('d M Y, h:i A');

        try {
            Resend::emails()->send([
                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                'to'      => $email,
                'subject' => 'Reset your KynexEdu password',
                'html'    => view('emails.school-reset-password', [
                    'name'      => $name,
                    'resetUrl'  => $resetUrl,
                    'expiresAt' => $expiresAt,
                ])->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function provisionFromInvitation(SchoolInvitation $invitation, string $password): void
    {
        // Create tenant
        $tenantId = Str::slug($invitation->school_name).'-'.Str::random(6);

        $tenant = Tenant::create([
            'id'          => $tenantId,
            'school_name' => $invitation->school_name,
            'admin_name'  => $invitation->contact_name,
            'admin_email' => $invitation->email,
            'status'      => 'trial',
        ]);

        // Provision tenant DB + create admin user with provided password
        $provisioner = new ProvisionNewTenant();
        $provisioner->invokeWithPassword($tenant, $password);

        // Link invitation to tenant
        $invitation->update(['tenant_id' => $tenant->id]);
    }

    private function activateInvitedUser(SchoolInvitation $invitation, string $password): void
    {
        if (! $invitation->tenant_id) {
            return;
        }

        $tenant = Tenant::find($invitation->tenant_id);
        if (! $tenant) {
            return;
        }

        tenancy()->initialize($tenant);

        $user = SchoolUser::where('email', $invitation->email)->first();
        if ($user) {
            $user->update([
                'password'           => $password,
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]);
        }

        tenancy()->end();
    }
}
