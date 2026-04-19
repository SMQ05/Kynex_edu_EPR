<?php

use App\Jobs\NotifyAbsentStudentsAndParents;
use App\Jobs\ProcessBiometricLogs;
use App\Jobs\OverdueHomeworkReminder;
use App\Jobs\RunScheduledReport;
use App\Jobs\SyncTenantActiveStudentCount;
use App\Models\Tenant;
use App\Models\Tenant\CustomReport;
use App\Services\FeesService;
use App\Services\LibraryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Stancl\Tenancy\Facades\Tenancy;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────────────────────────
// Central Schedules
// ─────────────────────────────────────────────────────────────────

/**
 * Sync active student counts for all active tenants every day at 2 AM.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', '!=', 'expired')
        ->each(function (Tenant $tenant) {
            SyncTenantActiveStudentCount::dispatch($tenant->id);
        });
})->dailyAt('02:00')
  ->name('sync-tenant-student-counts')
  ->withoutOverlapping();

/**
 * Generate monthly invoices on the 1st of every month at 3 AM.
 */
Schedule::command('billing:generate-invoices')
    ->monthlyOn(1, '03:00')
    ->name('generate-monthly-invoices')
    ->withoutOverlapping()
    ->onOneServer();

/**
 * Send billing reminders 5 days before due date — daily at 9 AM.
 */
Schedule::command('billing:send-notifications --type=reminder')
    ->dailyAt('09:00')
    ->name('billing-reminders')
    ->withoutOverlapping();

/**
 * Send overdue billing notifications — daily at 10 AM.
 */
Schedule::command('billing:send-notifications --type=overdue')
    ->dailyAt('10:00')
    ->name('billing-overdue-notifications')
    ->withoutOverlapping();

// ─────────────────────────────────────────────────────────────────
// Tenant Schedules (run within each tenant context)
// ─────────────────────────────────────────────────────────────────

/**
 * Process biometric attendance logs every 15 minutes.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                ProcessBiometricLogs::dispatch();
            });
        });
})->everyFifteenMinutes()
  ->name('process-biometric-logs')
  ->withoutOverlapping();

/**
 * Apply late fees for overdue student fees — daily at 1 AM.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                $feesService = app(FeesService::class);
                // Default: PKR 50/day late fee, max PKR 5000
                $feesService->applyLateFees(
                    finePerDayPaisas: 5000,
                    maxFinePaisas: 500000,
                );
            });
        });
})->dailyAt('01:00')
  ->name('apply-late-fees')
  ->withoutOverlapping();

/**
 * Mark overdue library books — daily at midnight.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                $libraryService = app(LibraryService::class);
                $libraryService->markOverdueBooks();
            });
        });
})->dailyAt('00:00')
  ->name('mark-overdue-library-books')
  ->withoutOverlapping();

/**
 * Notify parents AND students of absent students via WhatsApp/SMS — daily at 2 PM.
 * Runs after attendance is typically marked for the day.
 *
 * Changed from NotifyAbsentParents to NotifyAbsentStudentsAndParents (Phase 9.1)
 * to notify both guardians (WhatsApp/SMS) and students (in-app).
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                NotifyAbsentStudentsAndParents::dispatch();
            });
        });
})->dailyAt('14:00')
  ->name('notify-absent-students-and-parents')
  ->withoutOverlapping();

/**
 * Send overdue homework reminders to guardians — daily at 07:00 (before school starts).
 * Checks assignments 1-7 days past due_date and notifies guardians of students
 * who have not yet submitted. Phase 9.2.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                OverdueHomeworkReminder::dispatch();
            });
        });
})->dailyAt('07:00')
  ->name('overdue-homework-reminders')
  ->withoutOverlapping();

// ─────────────────────────────────────────────────────────────────
// Scheduled Report Runners (Custom Report Builder)
// ─────────────────────────────────────────────────────────────────

/**
 * Run daily scheduled reports at 6 AM every day.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                CustomReport::where('schedule_frequency', 'daily')
                    ->whereNotNull('schedule_email')
                    ->each(function (CustomReport $report) {
                        RunScheduledReport::dispatch($report);
                    });
            });
        });
})->dailyAt('06:00')
  ->name('run-daily-scheduled-reports')
  ->withoutOverlapping();

/**
 * Run weekly scheduled reports every Monday at 6:30 AM.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                CustomReport::where('schedule_frequency', 'weekly')
                    ->whereNotNull('schedule_email')
                    ->each(function (CustomReport $report) {
                        RunScheduledReport::dispatch($report);
                    });
            });
        });
})->weeklyOn(1, '06:30')
  ->name('run-weekly-scheduled-reports')
  ->withoutOverlapping();

/**
 * Run monthly scheduled reports on the 1st at 7 AM.
 */
Schedule::call(function () {
    Tenant::on('central')
        ->where('status', 'active')
        ->each(function (Tenant $tenant) {
            $tenant->run(function () {
                CustomReport::where('schedule_frequency', 'monthly')
                    ->whereNotNull('schedule_email')
                    ->each(function (CustomReport $report) {
                        RunScheduledReport::dispatch($report);
                    });
            });
        });
})->monthlyOn(1, '07:00')
  ->name('run-monthly-scheduled-reports')
  ->withoutOverlapping();

// ─────────────────────────────────────────────────────────────────
// Approval Workflow (Phase 10.5)
// ─────────────────────────────────────────────────────────────────

/**
 * Expires approval requests past their expires_at timestamp — runs at 00:30 daily.
 */
Schedule::call(function () {
    app(\App\Services\ApprovalService::class)->expireOverdue();
})->daily()->at('00:30')->name('expire-overdue-approvals')
  ->withoutOverlapping();

/**
 * Reset demo tenant data on the 1st of each month at 4 AM.
 */
Schedule::command('tenant:reset-demo')
    ->monthlyOn(1, '04:00')
    ->name('reset-demo-tenant')
    ->withoutOverlapping()
    ->onOneServer();

// ─────────────────────────────────────────────────────────────────
// Custom Domain Verification (Phase 15C.5)
// ─────────────────────────────────────────────────────────────────

/**
 * Verify pending custom domain DNS records every 15 minutes.
 * Domains older than 7 days are skipped automatically by the command.
 */
Schedule::command('kynex:verify-pending-domains')
    ->everyFifteenMinutes()
    ->name('verify-pending-domains')
    ->withoutOverlapping();

// ─────────────────────────────────────────────────────────────────
// Materialized View Refresh (Phase 15B.2)
// ─────────────────────────────────────────────────────────────────

/**
 * Refresh attendance and fee collection materialized views daily at 2:30 AM.
 * Uses CONCURRENTLY to avoid locking reads during refresh.
 */
Schedule::command('kynex:refresh-materialized-views')
    ->dailyAt('02:30')
    ->name('refresh-materialized-views')
    ->withoutOverlapping()
    ->runInBackground();

// ─────────────────────────────────────────────────────────────────
// PII Audit Log Pruning (FERPA 7-year retention)
// ─────────────────────────────────────────────────────────────────

/**
 * Prune PII access log records older than 7 years (2555 days).
 * Runs monthly — retention period is long so monthly is sufficient.
 */
Schedule::command('kynex:prune-pii-log')
    ->monthly()
    ->name('prune-pii-log')
    ->withoutOverlapping();
