<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FeeController;
use App\Http\Controllers\Api\V1\HomeworkController;
use App\Http\Controllers\Api\V1\NoticeController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\ResultController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\TimetableController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Rate Limiters
|--------------------------------------------------------------------------
*/

RateLimiter::for('api-global', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('api-auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

/*
|--------------------------------------------------------------------------
| Tenant API Routes (v1)
|--------------------------------------------------------------------------
|
| These routes are scoped to the identified tenant via subdomain.
| All routes live under the /api/v1/ prefix.
|
| Middleware stack:
|   - Tenant identification (via subdomain / stancl tenancy)
|   - EnsureApiAccess (checks plan.api_access)
|   - Sanctum auth for protected endpoints
|   - Rate limiting: 120 req/min authenticated, 10 req/min auth endpoints
|
*/

Route::prefix('api/v1')->middleware([
    'api',
    \App\Http\Middleware\EnsureApiAccess::class,
    'throttle:api-global',
])->group(function () {

    // ── Public Auth Endpoints ─────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-auth')
        ->name('api.v1.auth.login');

    // ── Protected Endpoints (require Sanctum token) ──────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth management
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('api.v1.auth.refresh');

        // Students
        Route::get('students', [StudentController::class, 'index'])->name('api.v1.students.index');
        Route::get('students/{id}', [StudentController::class, 'show'])->name('api.v1.students.show');
        Route::post('students', [StudentController::class, 'store'])->name('api.v1.students.store');

        // Attendance
        Route::get('attendance', [AttendanceController::class, 'index'])->name('api.v1.attendance.index');
        Route::get('attendance/summary', [AttendanceController::class, 'summary'])->name('api.v1.attendance.summary');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('api.v1.attendance.store');

        // Results
        Route::get('results', [ResultController::class, 'index'])->name('api.v1.results.index');
        Route::get('results/{id}', [ResultController::class, 'show'])->name('api.v1.results.show');

        // Fees
        Route::get('fees/{studentId}', [FeeController::class, 'show'])->name('api.v1.fees.show');

        // Timetable
        Route::get('timetable', [TimetableController::class, 'index'])->name('api.v1.timetable.index');

        // Homework
        Route::get('homework', [HomeworkController::class, 'index'])->name('api.v1.homework.index');
        Route::get('homework/{id}', [HomeworkController::class, 'show'])->name('api.v1.homework.show');
        Route::post('homework/{id}/submit', [HomeworkController::class, 'submit'])->name('api.v1.homework.submit');

        // Notices
        Route::get('notices', [NoticeController::class, 'index'])->name('api.v1.notices.index');
        Route::get('notices/{id}', [NoticeController::class, 'show'])->name('api.v1.notices.show');

        // Push notification tokens
        Route::post('push/register', [PushTokenController::class, 'register'])->name('api.v1.push.register');
        Route::post('push/unregister', [PushTokenController::class, 'unregister'])->name('api.v1.push.unregister');
    });
});
