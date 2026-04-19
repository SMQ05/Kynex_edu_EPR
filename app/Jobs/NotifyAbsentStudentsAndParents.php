<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyAbsentStudentsAndParents — Sends absence notifications to guardians
 * AND records an in-app notification for the student (if they have a portal account).
 *
 * Renamed from NotifyAbsentParents (Phase 7 Part 3a).
 * The old class is kept as an alias for backward compatibility.
 *
 * Dispatched after attendance is marked.
 * For each absent student, notifies the primary guardian via
 * NotificationService with trigger 'student.absent'.
 */
class NotifyAbsentStudentsAndParents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<string>  $studentIds  IDs of absent students
     * @param  string         $date        The attendance date (Y-m-d)
     */
    public function __construct(
        public array $studentIds,
        public string $date,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        foreach ($this->studentIds as $studentId) {
            try {
                $student = Student::with(['schoolClass', 'guardians'])->find($studentId);

                if (! $student) {
                    continue;
                }

                $schoolName = config('app.name', 'School');
                $className  = $student->schoolClass?->name ?? 'N/A';

                $templateData = [
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'class'        => $className,
                    'date'         => $this->date,
                    'school_name'  => $schoolName,
                ];

                // ── 1. Notify Primary Guardian ────────────────────────────
                $guardian = $student->guardians()
                    ->where('is_primary_contact', true)
                    ->whereNotNull('whatsapp')
                    ->first();

                if (! $guardian) {
                    $guardian = $student->guardians()
                        ->whereNotNull('whatsapp')
                        ->first();
                }

                if ($guardian) {
                    $notificationService->sendFromTemplate(
                        templateSlug: 'student.absent',
                        notifiable:   $guardian,
                        data:         $templateData,
                        eventTrigger: 'student.absent',
                    );
                } else {
                    Log::info("NotifyAbsentStudentsAndParents: No guardian with WhatsApp for student {$studentId}");
                }

                // ── 2. In-app notification for student (if they have account) ──
                $studentUser = $student->schoolUser ?? null;
                if ($studentUser) {
                    NotificationService::sendImmediate(
                        channel:      'in_app',
                        userId:       $studentUser->id,
                        body:         "Your absence on {$this->date} has been recorded.",
                        eventTrigger: 'student.absent',
                        variables:    $templateData,
                    );
                }

                // ── 3. Direct WhatsApp/SMS to student ────────────────────
                $notificationService->sendToStudentDirectly(
                    student:      $student,
                    title:        'Absence Recorded',
                    body:         "Dear {$student->first_name}, your absence on {$this->date} in {$className} has been recorded. — {$schoolName}",
                    eventTrigger: 'student.absent',
                );

                // ── 4. Mark attendance record as notified ─────────────────
                AttendanceRecord::where('student_id', $studentId)
                    ->whereDate('date', $this->date)
                    ->whereNull('notified_at')
                    ->update(['notified_at' => now()]);

            } catch (\Throwable $e) {
                Log::error("NotifyAbsentStudentsAndParents: Failed for student {$studentId}: {$e->getMessage()}");
            }
        }
    }
}
