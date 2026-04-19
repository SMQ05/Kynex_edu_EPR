<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentGuardian;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyAbsentParents — Sends absence notifications to guardians.
 *
 * Dispatched asynchronously after attendance is marked.
 * For each absent student, notifies the primary guardian via
 * the NotificationService with trigger 'student.absent'.
 */
class NotifyAbsentParents implements ShouldQueue
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

                // Get primary guardian with whatsapp number
                $guardian = $student->guardians()
                    ->where('is_primary_contact', true)
                    ->whereNotNull('whatsapp')
                    ->first();

                if (! $guardian) {
                    // Fallback: any guardian with whatsapp
                    $guardian = $student->guardians()
                        ->whereNotNull('whatsapp')
                        ->first();
                }

                if (! $guardian) {
                    Log::info("NotifyAbsentParents: No guardian with WhatsApp for student {$studentId}");
                    continue;
                }

                $schoolName = config('app.name', 'School');
                $className = $student->schoolClass?->name ?? 'N/A';

                $notificationService->sendFromTemplate(
                    templateSlug: 'student-absent',
                    notifiable: $guardian,
                    data: [
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'class'        => $className,
                        'date'         => $this->date,
                        'school_name'  => $schoolName,
                    ],
                    eventTrigger: 'student.absent',
                );

                // Mark attendance record as notified
                AttendanceRecord::where('student_id', $studentId)
                    ->whereDate('date', $this->date)
                    ->whereNull('notified_at')
                    ->update(['notified_at' => now()]);

            } catch (\Throwable $e) {
                Log::error("NotifyAbsentParents: Failed for student {$studentId}: {$e->getMessage()}");
            }
        }
    }
}
