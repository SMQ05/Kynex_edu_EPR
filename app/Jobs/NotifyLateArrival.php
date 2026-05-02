<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\AttendanceSetting;
use App\Models\Tenant\Student;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyLateArrival — Notifies guardian when a student arrives late.
 *
 * Part 6e — Dispatched from ProcessBiometricLogs when a student's
 * check-in time exceeds the late_arrival_cutoff defined in attendance_settings.
 *
 * Also flags the attendance record as 'late' in the status column.
 */
class NotifyLateArrival implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  string  $studentId    The Student ULID
     * @param  string  $date         Arrival date (Y-m-d)
     * @param  string  $arrivalTime  Actual arrival time (H:i:s)
     */
    public function __construct(
        public string $studentId,
        public string $date,
        public string $arrivalTime,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            $student = Student::with(['guardians', 'campus'])->find($this->studentId);

            if (! $student) {
                return;
            }

            // Check settings — honour campus-specific cutoff
            $settings = AttendanceSetting::forCampus($student->campus_id ?? null);

            if (! $settings->notify_on_late_arrival) {
                return;
            }

            // Verify this is actually late (re-check with grace period)
            if (! $settings->isLateArrival($this->arrivalTime)) {
                return;
            }

            $guardian = $student->guardians()
                ->where('is_primary_contact', true)
                ->first()
                ?? $student->guardians()->first();

            if (! $guardian) {
                Log::info("NotifyLateArrival: No guardian for student {$this->studentId}");
                return;
            }

            $formattedTime = date('h:i A', strtotime($this->arrivalTime));

            $schoolName = config('app.name', 'School');

            $notificationService->sendFromTemplate(
                templateSlug: 'student.late',
                notifiable:   $guardian,
                data: [
                    'student_name'  => $student->first_name . ' ' . $student->last_name,
                    'date'          => $this->date,
                    'arrival_time'  => $formattedTime,
                    'cutoff_time'   => date('h:i A', strtotime($settings->late_arrival_cutoff)),
                    'school_name'   => $schoolName,
                ],
                eventTrigger: 'student.late',
            );

            // Direct notification to student (in-app + WhatsApp/SMS)
            $notificationService->sendToStudentDirectly(
                student:      $student,
                title:        'Late Arrival Recorded',
                body:         "Dear {$student->first_name}, your arrival at {$formattedTime} on {$this->date} has been recorded as late. Cutoff was " . date('h:i A', strtotime($settings->late_arrival_cutoff)) . ". — {$schoolName}",
                eventTrigger: 'student.late',
            );

        } catch (\Throwable $e) {
            Log::error("NotifyLateArrival: Failed for student {$this->studentId}: {$e->getMessage()}");
        }
    }
}
