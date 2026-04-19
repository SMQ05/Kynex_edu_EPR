<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\StudentGuardian;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyHomeworkGraded — Part 4g.
 *
 * Dispatched when a teacher grades (sets marks + feedback on) a homework
 * submission. Notifies the student's primary guardian via the
 * 'homework.graded' template.
 *
 * Dispatch after saving marks:
 *   NotifyHomeworkGraded::dispatch($submission->id);
 */
class NotifyHomeworkGraded implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $submissionId,
    ) {}

    public function handle(NotificationService $service): void
    {
        $submission = HomeworkSubmission::with(['homework.subject', 'homework.schoolClass', 'student'])->find($this->submissionId);

        if (! $submission) {
            Log::warning("NotifyHomeworkGraded: Submission {$this->submissionId} not found.");
            return;
        }

        if (! $submission->isGraded()) {
            Log::info("NotifyHomeworkGraded: Submission {$this->submissionId} is not yet graded — skipping.");
            return;
        }

        $student = $submission->student;

        if (! $student) {
            Log::warning("NotifyHomeworkGraded: No student on submission {$this->submissionId}.");
            return;
        }

        // Find primary guardian
        $guardian = StudentGuardian::where('student_id', $student->id)
            ->where('is_primary', true)
            ->first();

        if (! $guardian) {
            Log::info("NotifyHomeworkGraded: No primary guardian for student {$student->id}.");
            return;
        }

        $homework = $submission->homework;

        $variables = [
            'student_name'   => $student->full_name ?? ($student->first_name . ' ' . $student->last_name),
            'subject'        => $homework?->subject?->name ?? 'N/A',
            'class'          => $homework?->schoolClass?->name ?? 'N/A',
            'marks_obtained' => $submission->marks_obtained ?? 'N/A',
            'total_marks'    => $submission->total_marks ?? 'N/A',
            'feedback'       => $submission->feedback ?? 'No feedback provided.',
            'homework_title' => $homework?->title ?? 'Homework',
        ];

        $service->sendFromTemplate(
            templateSlug: 'homework.graded',
            notifiable: $guardian,
            data: $variables,
        );

        // Direct notification to student (in-app + WhatsApp/SMS)
        $service->sendToStudentDirectly(
            student:      $student,
            title:        'Homework Graded',
            body:         "Dear {$student->first_name}, your {$homework?->subject?->name} homework \"{$homework?->title}\" has been graded. Marks: {$submission->marks_obtained}/{$submission->total_marks}.",
            eventTrigger: 'homework.graded',
        );

        Log::info("NotifyHomeworkGraded: Graded notification sent for submission {$this->submissionId}.");
    }
}
