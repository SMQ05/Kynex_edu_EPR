<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\Student;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyHomeworkCreated — Notifies parents/guardians when new homework is assigned.
 *
 * Part 4e — Dispatched from HomeworkAssignment observer or controller
 * after a new homework record is created.
 *
 * Notifies the primary guardian of every enrolled student in the target
 * class/section via 'homework.assigned' notification template.
 */
class NotifyHomeworkCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    /**
     * @param  string  $homeworkId  The HomeworkAssignment ULID
     */
    public function __construct(
        public string $homeworkId,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $homework = HomeworkAssignment::with(['schoolClass', 'section', 'subject', 'teacher'])
            ->find($this->homeworkId);

        if (! $homework) {
            Log::warning("NotifyHomeworkCreated: Homework {$this->homeworkId} not found.");
            return;
        }

        // Get all enrolled students in the class/section
        $query = Student::with(['guardians'])
            ->where('class_id', $homework->class_id)
            ->where('status', 'enrolled');

        if ($homework->section_id) {
            $query->where('section_id', $homework->section_id);
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            return;
        }

        $schoolName  = config('app.name', 'School');
        $className   = $homework->schoolClass?->name ?? 'N/A';
        $sectionName = $homework->section?->name ?? '';
        $subjectName = $homework->subject?->name ?? 'N/A';
        $teacherName = $homework->teacher?->name ?? 'Teacher';
        $dueDate     = $homework->due_date->format('M d, Y');

        $sent    = 0;
        $skipped = 0;

        foreach ($students as $student) {
            try {
                $guardian = $student->guardians()
                    ->where('is_primary_contact', true)
                    ->first()
                    ?? $student->guardians()->first();

                if (! $guardian) {
                    $skipped++;
                    continue;
                }

                $notificationService->sendFromTemplate(
                    templateSlug: 'homework.assigned',
                    notifiable:   $guardian,
                    data: [
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'class'        => $className . ($sectionName ? " ({$sectionName})" : ''),
                        'subject'      => $subjectName,
                        'homework'     => $homework->title,
                        'due_date'     => $dueDate,
                        'teacher_name' => $teacherName,
                        'school_name'  => $schoolName,
                    ],
                    eventTrigger: 'homework.assigned',
                );

                // Direct notification to student (in-app + WhatsApp/SMS)
                $notificationService->sendToStudentDirectly(
                    student:      $student,
                    title:        'New Homework Assigned',
                    body:         "Dear {$student->first_name}, new {$subjectName} homework \"{$homework->title}\" has been assigned. Due: {$dueDate}. — {$schoolName}",
                    eventTrigger: 'homework.assigned',
                );

                $sent++;

            } catch (\Throwable $e) {
                Log::error("NotifyHomeworkCreated: Failed for student {$student->id}: {$e->getMessage()}");
            }
        }

        Log::info("NotifyHomeworkCreated: Notified {$sent} guardians, {$skipped} skipped (no guardian) for homework {$this->homeworkId}.");
    }
}
