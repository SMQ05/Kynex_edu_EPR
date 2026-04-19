<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AttendanceStatus;
use App\Enums\StaffAttendanceStatus;
use App\Models\SchoolUser;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\AttendanceSetting;
use App\Models\Tenant\BiometricLog;
use App\Models\Tenant\StaffAttendanceRecord;
use App\Models\Tenant\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessBiometricLogs — Processes unprocessed biometric punch logs.
 *
 * Phase 6 update: Added device_user_id resolution step.
 * When ZKTeco pushes logs, the AdmsController resolves device_user_id
 * at insert time. However, if a log arrives before the user is mapped
 * (school_user_id and student_id are both null), this job re-attempts
 * resolution using the biometric_device_id column on SchoolUser/Student.
 *
 * Part 6d update: After creating/updating student attendance, checks
 * if the student arrived late (using AttendanceSetting::forCampus()),
 * and if notify_on_late_arrival is enabled, dispatches NotifyLateArrival.
 *
 * Groups punches by user/student + date, determines check-in/out,
 * and creates/updates attendance records accordingly.
 * Scheduled every 15 minutes for active tenants.
 */
class ProcessBiometricLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $unprocessed = BiometricLog::unprocessed()
            ->orderBy('punch_time')
            ->get();

        if ($unprocessed->isEmpty()) {
            return;
        }

        // ── Step 0: Resolve unlinked device_user_ids ─────────────────
        $this->resolveUnlinkedLogs($unprocessed);

        // ── Step 1: Process staff attendance ─────────────────────────
        $staffGroups = $unprocessed->whereNotNull('school_user_id')
            ->groupBy(fn (BiometricLog $log) => $log->school_user_id . '|' . $log->punch_time->toDateString());

        foreach ($staffGroups as $key => $logs) {
            try {
                [$userId, $date] = explode('|', $key);
                $sorted = $logs->sortBy('punch_time');

                $checkIn  = $sorted->first();
                $checkOut = $sorted->count() > 1 ? $sorted->last() : null;

                StaffAttendanceRecord::updateOrCreate(
                    [
                        'school_user_id' => $userId,
                        'date'           => $date,
                    ],
                    [
                        'check_in_time'  => $checkIn->punch_time->format('H:i:s'),
                        'check_out_time' => $checkOut?->punch_time->format('H:i:s'),
                        'status'         => StaffAttendanceStatus::Present->value,
                    ]
                );

                // Mark check-in/check-out types
                $checkIn->update(['punch_type' => 'check_in', 'is_processed' => true]);
                if ($checkOut && $checkOut->id !== $checkIn->id) {
                    $checkOut->update(['punch_type' => 'check_out', 'is_processed' => true]);
                }

                // Mark remaining logs as processed
                $logs->whereNotIn('id', array_filter([$checkIn->id, $checkOut?->id]))
                    ->each(fn (BiometricLog $l) => $l->update(['is_processed' => true]));

            } catch (\Throwable $e) {
                Log::error("ProcessBiometricLogs: Staff group {$key} failed: {$e->getMessage()}");
            }
        }

        // ── Step 2: Process student attendance ───────────────────────
        $studentGroups = $unprocessed->whereNotNull('student_id')
            ->groupBy(fn (BiometricLog $log) => $log->student_id . '|' . $log->punch_time->toDateString());

        foreach ($studentGroups as $key => $logs) {
            try {
                [$studentId, $date] = explode('|', $key);
                $student = Student::find($studentId);

                if (! $student) {
                    $logs->each(fn (BiometricLog $l) => $l->update(['is_processed' => true]));
                    continue;
                }

                $sorted  = $logs->sortBy('punch_time');
                $checkIn = $sorted->first();

                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date'       => $date,
                    ],
                    [
                        'class_id'   => $student->class_id,
                        'section_id' => $student->section_id,
                        'status'     => AttendanceStatus::Present->value,
                    ]
                );

                $logs->each(fn (BiometricLog $l) => $l->update(['is_processed' => true]));

                // ── Part 6d: Late arrival detection ───────────────────
                $this->checkLateArrival($student, $date, $checkIn->punch_time->format('H:i'));

            } catch (\Throwable $e) {
                Log::error("ProcessBiometricLogs: Student group {$key} failed: {$e->getMessage()}");
            }
        }

        // ── Step 3: Mark orphan logs (no user match) as processed ────
        $orphans = $unprocessed->filter(
            fn (BiometricLog $log) => is_null($log->school_user_id) && is_null($log->student_id)
        );

        if ($orphans->isNotEmpty()) {
            Log::warning("ProcessBiometricLogs: {$orphans->count()} orphan logs (no matching user) — skipping.");
            // Don't mark as processed so they can be resolved later when user is mapped
        }

        $processed = $unprocessed->count() - $orphans->count();
        Log::info("ProcessBiometricLogs: Processed {$processed} logs, {$orphans->count()} orphans remaining.");
    }

    /**
     * Part 6d: Check if a student arrived late and dispatch notification.
     *
     * Uses AttendanceSetting::forCampus() to get class/section-specific,
     * campus-specific, or school-wide settings. If notify_on_late_arrival
     * is enabled and the student's arrival time exceeds the cutoff + grace
     * period, dispatches NotifyLateArrival.
     */
    private function checkLateArrival(Student $student, string $date, string $arrivalTime): void
    {
        try {
            $setting = AttendanceSetting::forCampus(
                campusId:  $student->campus_id ?? null,
                classId:   $student->class_id ?? null,
                sectionId: $student->section_id ?? null,
            );

            if (! $setting) {
                return; // No settings configured — skip late check
            }

            if (! $setting->notify_on_late_arrival) {
                return; // Notifications disabled for this campus
            }

            if ($setting->isLateArrival($arrivalTime)) {
                NotifyLateArrival::dispatch($student->id, $date, $arrivalTime);
                Log::info("ProcessBiometricLogs: Late arrival detected for student {$student->id} at {$arrivalTime}.");
            }
        } catch (\Throwable $e) {
            // Don't fail the whole job just because late-check failed
            Log::warning("ProcessBiometricLogs: Late arrival check failed for student {$student->id}: {$e->getMessage()}");
        }
    }

    /**
     * Attempt to resolve unlinked biometric logs.
     *
     * When a ZKTeco device pushes a punch with device_user_id but we couldn't
     * match it at the time of insertion (user not yet mapped), try again here
     * by checking SchoolUser.biometric_device_id and Student.biometric_device_id.
     */
    private function resolveUnlinkedLogs($logs): void
    {
        $unlinked = $logs->filter(
            fn (BiometricLog $log) => is_null($log->school_user_id)
                && is_null($log->student_id)
                && ! empty($log->device_user_id)
        );

        if ($unlinked->isEmpty()) {
            return;
        }

        // Batch-load mappings to avoid N+1
        $deviceUserIds = $unlinked->pluck('device_user_id')->unique()->values();

        $staffMap = SchoolUser::whereIn('biometric_device_id', $deviceUserIds)
            ->pluck('id', 'biometric_device_id');

        $studentMap = Student::whereIn('biometric_device_id', $deviceUserIds)
            ->pluck('id', 'biometric_device_id');

        $resolved = 0;

        foreach ($unlinked as $log) {
            $did = $log->device_user_id;

            if (isset($staffMap[$did])) {
                $log->school_user_id = $staffMap[$did];
                $log->save();
                $resolved++;
            } elseif (isset($studentMap[$did])) {
                $log->student_id = $studentMap[$did];
                $log->save();
                $resolved++;
            }
        }

        if ($resolved > 0) {
            Log::info("ProcessBiometricLogs: Resolved {$resolved} previously unlinked logs.");
        }
    }
}
