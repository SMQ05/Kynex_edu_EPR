<?php

use App\Http\Controllers\Payment\CallbackController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\Cms\PublicController;
use App\Http\Controllers\WhatsApp\BotController;
use App\Http\Controllers\Zkteco\AdmsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by Stancl\Tenancy\TenancyRouteLoader into the
| router when a tenant is identified (via subdomain). They are prefixed
| with the tenant's slug and scoped to the tenant's database.
|
| Routes here have tenancy initialized automatically.
|
*/

// ── Payment Gateway Callbacks (no auth, tenant-aware) ────────────────
Route::post('/payment/jazzcash/callback', [CallbackController::class, 'jazzcashCallback'])
    ->name('payment.jazzcash.callback');
Route::post('/payment/easypaisa/callback', [CallbackController::class, 'easypaisaCallback'])
    ->name('payment.easypaisa.callback');
Route::get('/payment/jazzcash/return', [CallbackController::class, 'jazzcashReturn'])
    ->name('payment.jazzcash.return');
Route::get('/payment/easypaisa/return', [CallbackController::class, 'easypaisaReturn'])
    ->name('payment.easypaisa.return');

// ── Public Website (CMS) Routes ──────────────────────────────────────
Route::get('/', [PublicController::class, 'home'])->name('tenant.home');
Route::get('/about', [PublicController::class, 'about'])->name('tenant.about');
Route::get('/admissions', [PublicController::class, 'admissions'])->name('tenant.admissions');
Route::get('/gallery', [PublicController::class, 'gallery'])->name('tenant.gallery');
Route::get('/news', [PublicController::class, 'news'])->name('tenant.news');
Route::get('/news/{slug}', [PublicController::class, 'newsShow'])->name('tenant.news.show');
Route::get('/contact', [PublicController::class, 'contact'])->name('tenant.contact');
Route::post('/contact-form', [PublicController::class, 'contactForm'])->name('tenant.contact.form');
Route::get('/results', [PublicController::class, 'results'])->name('tenant.results');
Route::post('/results', [PublicController::class, 'resultsSearch'])->name('tenant.results.search');
Route::get('/pages/{slug}', [PublicController::class, 'page'])->name('tenant.page');

// ── ZKTeco ADMS Push Protocol Routes (no auth — device uses SN) ──────
Route::prefix('iclock')->group(function () {
    Route::match(['get', 'post'], '/cdata', [AdmsController::class, 'cdata'])
        ->name('zkteco.cdata');
    Route::get('/getrequest', [AdmsController::class, 'getrequest'])
        ->name('zkteco.getrequest');
    Route::post('/devicecmd', [AdmsController::class, 'devicecmd'])
        ->name('zkteco.devicecmd');
});

// ── WhatsApp Bot Webhook Routes (no auth — verified by token/signature) ──
Route::prefix('whatsapp')->group(function () {
    Route::get('/webhook', [BotController::class, 'verify'])
        ->name('whatsapp.verify');
    Route::post('/webhook', [BotController::class, 'webhook'])
        ->name('whatsapp.webhook');
});

