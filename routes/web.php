<?php

use App\Http\Controllers\ExamLoginController;
use App\Http\Controllers\StudentBulkImportController;
use App\Http\Controllers\FeeReceiptController;
use App\Http\Controllers\FinancialReportPrintController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PublicAdmissionCompleteController;
use App\Http\Controllers\PublicAdmissionController;
use App\Http\Controllers\PublicAdmissionTestController;
use App\Http\Controllers\ResultCardController;
use App\Http\Controllers\SchoolPortalController;
use App\Http\Middleware\EnsureCentralHost;
use App\Http\Middleware\InitializeTenancyBySubdomainOrDomain;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Database\Models\Domain;

// ─────────────────────────────────────────────────────────────────
// Tenant-aware download routes that work on both central (with
// session tenant_id) and tenant subdomains. Tenancy initializes
// from session if running on a central host.
// ─────────────────────────────────────────────────────────────────
Route::middleware([InitializeTenancyBySubdomainOrDomain::class, 'auth:school_users'])->group(function () {
    Route::post('/admin/bulk-import-run', [StudentBulkImportController::class, 'run'])
        ->name('admin.bulk-import.run');

    Route::get('/payslip/{payroll}/download', [PayslipController::class, 'download'])
        ->name('payslip.download');

    Route::get('/result-card/{result}', [ResultCardController::class, 'download'])
        ->name('result-card.pdf');

    Route::get('/financial-report/print', [FinancialReportPrintController::class, 'show'])
        ->name('financial-report.print');

    Route::get('/fee-receipt/{payment}', [FeeReceiptController::class, 'show'])
        ->name('fee.receipt');
});

// ─────────────────────────────────────────────────────────────────
// Public student admission flow — runs under tenant subdomain so the
// applicant submits against a specific school.
// ─────────────────────────────────────────────────────────────────
Route::middleware([InitializeTenancyBySubdomainOrDomain::class])
    ->name('public.')
    ->group(function () {
        Route::get('/apply', [PublicAdmissionController::class, 'show'])->name('apply');
        Route::post('/apply', [PublicAdmissionController::class, 'submit'])->name('apply.submit');
        Route::get('/apply/status/{token}', [PublicAdmissionController::class, 'status'])->name('apply.status');

        Route::get('/parent/register', [PublicAdmissionController::class, 'showParentRegister'])->name('parent.register');
        Route::post('/parent/register', [PublicAdmissionController::class, 'submitParentRegister'])->name('parent.register.submit');

        // Exam-day temporary login for applicants.
        Route::get('/exam-login', [ExamLoginController::class, 'show'])->name('exam-login');
        Route::post('/exam-login', [ExamLoginController::class, 'login'])->name('exam-login.submit');

        // Admission profile completion (post-admit detailed form).
        Route::get('/admission/complete/{token}', [PublicAdmissionCompleteController::class, 'show'])->name('admission.complete');
        Route::post('/admission/complete/{token}', [PublicAdmissionCompleteController::class, 'submit'])->name('admission.complete.submit');

        // Exam waiting room — shown after login, polls until admin starts the exam.
        Route::get('/exam-waiting/{token}', [PublicAdmissionTestController::class, 'waiting'])->name('admission-test.waiting');
        Route::get('/exam-waiting/{token}/status', [PublicAdmissionTestController::class, 'waitingStatus'])->name('admission-test.waiting.status');

        // Online entry test (admission test) — token-based, no login.
        Route::get('/admission-test/{token}', [PublicAdmissionTestController::class, 'start'])->name('admission-test.start');
        Route::post('/admission-test/{token}/begin', [PublicAdmissionTestController::class, 'begin'])->name('admission-test.begin');
        Route::post('/admission-test/{token}/submit', [PublicAdmissionTestController::class, 'submit'])->name('admission-test.submit');
        Route::post('/admission-test/{token}/violation', [PublicAdmissionTestController::class, 'violation'])->name('admission-test.violation');
    });

// ─────────────────────────────────────────────────────────────────
// Tenant public website (CMS) — works on central domain via
// ?tenant=<id> as well as on tenant subdomains. Routes mirror those
// in routes/tenancy.php so the website is reachable both ways.
// ─────────────────────────────────────────────────────────────────
Route::middleware([InitializeTenancyBySubdomainOrDomain::class])->group(function () {
    Route::get('/site/{tenant}', [\App\Http\Controllers\PublicSiteController::class, 'home'])->name('public.site');
    Route::get('/site/{tenant}/about', [\App\Http\Controllers\PublicSiteController::class, 'about'])->name('public.site.about');
    Route::get('/site/{tenant}/admissions', [\App\Http\Controllers\PublicSiteController::class, 'admissions'])->name('public.site.admissions');
    Route::get('/site/{tenant}/gallery', [\App\Http\Controllers\PublicSiteController::class, 'gallery'])->name('public.site.gallery');
    Route::get('/site/{tenant}/news', [\App\Http\Controllers\PublicSiteController::class, 'news'])->name('public.site.news');
    Route::get('/site/{tenant}/news/{slug}', [\App\Http\Controllers\PublicSiteController::class, 'newsShow'])->name('public.site.news.show');
    Route::get('/site/{tenant}/contact', [\App\Http\Controllers\PublicSiteController::class, 'contact'])->name('public.site.contact');
    Route::post('/site/{tenant}/contact-form', [\App\Http\Controllers\PublicSiteController::class, 'contactForm'])->name('public.site.contact.form');
    Route::get('/site/{tenant}/results', [\App\Http\Controllers\PublicSiteController::class, 'results'])->name('public.site.results');
    Route::post('/site/{tenant}/results', [\App\Http\Controllers\PublicSiteController::class, 'resultsSearch'])->name('public.site.results.search');
    Route::get('/site/{tenant}/pages/{slug}', [\App\Http\Controllers\PublicSiteController::class, 'page'])->name('public.site.page');
});

// ─────────────────────────────────────────────────────────────────
// Redirect Filament's built-in /admin/login to our custom portal.
// The Filament login page queries school_users without tenancy context,
// which fails on the central domain. All login must go through /login.
//
// The name `filament.school-admin.auth.login` is the route name Filament
// expects when AuthenticateSession invalidates a session and calls
// Panel::getLoginUrl(). Binding the name here (instead of letting Filament
// register a real Login page via the panel's ->login()) keeps that helper
// resolvable while still funnelling users through the school portal login.
// ─────────────────────────────────────────────────────────────────
Route::get('/admin/login', fn () => redirect()->route('school.login'))
    ->name('filament.school-admin.auth.login');

// ─────────────────────────────────────────────────────────────────
// Root `/` — host-aware landing.
//   - Central host                  → SaaS marketing landing (portal.landing).
//     Checked first so a session-tenancy fallback on the central host
//     (set after a tenant admin logs in) does NOT cause the central / to
//     render the tenant CMS.
//   - Verified custom-domain host   → tenant CMS home (Cms\PublicController@home).
//   - Unknown / unverified host     → neutral 404 (errors.domain-not-configured).
//     The SaaS landing must never appear on a non-central host.
// ─────────────────────────────────────────────────────────────────
Route::middleware([InitializeTenancyBySubdomainOrDomain::class])->group(function () {
    Route::get('/', function () {
        $host = request()->getHost();
        $central = config('tenancy.central_domains', []);

        if (in_array($host, $central, true)) {
            return view('portal.landing');
        }
        if (tenancy()->initialized) {
            return app(\App\Http\Controllers\Cms\PublicController::class)->home();
        }
        return response()->view('errors.domain-not-configured', [], 404);
    })->name('school.landing');
});

// ─────────────────────────────────────────────────────────────────
// Resend click-tracking passthrough
//
// Resend wraps every link with https://sms.kynexsolutions.com/CL0/
// {url-encoded-destination}/1/{tracking-id}/{signature}.  Apache
// returns 404 for paths containing %2F unless AllowEncodedSlashes
// NoDecode is set (see docker/apache-vhost.conf).  Once the request
// reaches PHP, Laravel has decoded the path, so $destination is the
// plain URL we originally put in the email.
// ─────────────────────────────────────────────────────────────────
Route::get('/CL0/{path}', function (string $path) {
    // Resend's format: {destination}/1/{tracking-id}/{signature}
    // Apache may pass either decoded ("https://...") or still-encoded
    // ("https:%2F%2F...") form depending on AllowEncodedSlashes.
    $destination = \Illuminate\Support\Str::before($path, '/1/');

    // Decode if still URL-encoded (e.g. "https:%2F%2Faqmdigital.com%2F...").
    if (str_contains($destination, '%2F') || str_contains($destination, '%3A')) {
        $destination = urldecode($destination);
    }

    if (! filter_var($destination, FILTER_VALIDATE_URL)) {
        abort(404);
    }

    $destHost = parse_url($destination, PHP_URL_HOST);
    if (! $destHost) {
        abort(404);
    }

    // Whitelist: central host + verified custom domains + tenant subdomains.
    $allowed = false;

    // 1. Central host
    if ($destHost === parse_url(config('app.url'), PHP_URL_HOST)) {
        $allowed = true;
    }

    // 2. Verified custom domain
    if (! $allowed) {
        $allowed = \Stancl\Tenancy\Database\Models\Domain::query()
            ->where('domain', $destHost)
            ->where('is_verified', true)
            ->exists();
    }

    // 3. Tenant subdomain pattern (e.g. tenant-slug.kynexedu.com)
    if (! $allowed) {
        foreach (config('tenancy.central_domains', []) as $central) {
            if (str_ends_with($destHost, '.' . $central)) {
                $allowed = true;
                break;
            }
        }
    }

    if (! $allowed) {
        abort(404);
    }

    return redirect($destination, 302);
})->where('path', '.*');

// ─────────────────────────────────────────────────────────────────
// School Portal — Public pages at the central host.
// Schools self-register, verify email, set password, login, and
// reset password here. No SaaS admin login is shown here.
// ─────────────────────────────────────────────────────────────────
Route::name('school.')->group(function () {
    // ── Self-registration (central host only — SaaS-level concern) ──
    Route::middleware(EnsureCentralHost::class)->group(function () {
        Route::get('/register', [SchoolPortalController::class, 'showRegister'])->name('register');
        Route::post('/register', [SchoolPortalController::class, 'register'])->name('register.submit');
    });

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
