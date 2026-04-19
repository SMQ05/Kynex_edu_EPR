<?php

use App\Http\Controllers\SchoolPortalController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Database\Models\Domain;

// ─────────────────────────────────────────────────────────────────
// Redirect Filament's built-in /admin/login to our custom portal.
// The Filament login page queries school_users without tenancy context,
// which fails on the central domain. All login must go through /login.
// ─────────────────────────────────────────────────────────────────
Route::get('/admin/login', fn () => redirect()->route('school.login'))->name('admin.login.redirect');

// ─────────────────────────────────────────────────────────────────
// School Portal — Public landing page at edu.kynexsolutions.com
// Schools self-register, verify email, set password, login, and
// reset password here. No SaaS admin login is shown here.
// ─────────────────────────────────────────────────────────────────
Route::name('school.')->group(function () {
    // Landing page
    Route::get('/', [SchoolPortalController::class, 'landing'])->name('landing');

    // ── Self-registration ─────────────────────────────────────────
    Route::get('/register', [SchoolPortalController::class, 'showRegister'])->name('register');
    Route::post('/register', [SchoolPortalController::class, 'register'])->name('register.submit');

    // Email verification (link in email)
    Route::get('/verify-email/{token}', [SchoolPortalController::class, 'verifyEmail'])->name('verify-email');

    // ── Set / activate password (for both self-signup & admin invites) ──
    Route::get('/set-password/{token}', [SchoolPortalController::class, 'showSetPassword'])->name('set-password');
    Route::post('/set-password/{token}', [SchoolPortalController::class, 'setPassword'])->name('set-password.submit');

    // ── Login ─────────────────────────────────────────────────────
    Route::get('/login', [SchoolPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [SchoolPortalController::class, 'login'])->name('login.submit');
    Route::post('/logout', [SchoolPortalController::class, 'logout'])->name('logout');

    // ── Forgot / reset password ───────────────────────────────────
    Route::get('/forgot-password', [SchoolPortalController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [SchoolPortalController::class, 'sendResetLink'])->name('forgot-password.submit');
    Route::get('/reset-password/{token}', [SchoolPortalController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password/{token}', [SchoolPortalController::class, 'resetPassword'])->name('reset-password.submit');

    // ── Dashboard selector (after login) ─────────────────────────
    Route::get('/dashboard', [SchoolPortalController::class, 'dashboard'])->name('dashboard')->middleware('auth:school_users');
});

// ─────────────────────────────────────────────────────────────────
// Caddy On-Demand TLS Domain Check (Phase 15C.6)
// ─────────────────────────────────────────────────────────────────
// Caddy calls this endpoint before provisioning an SSL certificate.
// Returns 200 for verified domains, 404 otherwise.
// See docs/custom-domains-ssl.md for full setup.
Route::get('/caddy/check-domain', function () {
    $domain = request()->query('domain');

    if (! $domain) {
        return response('', 400);
    }

    $exists = Domain::where('domain', $domain)
        ->where('is_verified', true)
        ->exists();

    return response('', $exists ? 200 : 404);
});
