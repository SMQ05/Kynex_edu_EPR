<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\Exam;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\Student;
use App\Services\DeepLinkService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyResultPublished — Notifies guardians and students when exam results
 * are published (publish_results toggled to true).
 *
 * Phase 9.5 — Dispatched from ExamResource when publish_results is set to true.
 *
 * For each class/section covered by the exam schedules, loads enrolled students
 * and sends notifications via the tenant's enabled channels.
 */
class NotifyResultPublished implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Exam $exam,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $exam = $this->exam->load(['schedules.schoolClass', 'schedules.section']);

        $schoolName = config('app.name', 'School');

        // Collect unique class/section pairs from exam schedules
        $classSectionPairs = $exam->schedules
            ->map(fn ($schedule) => [
                'class_id'   => $schedule->class_id,
                'section_id' => $schedule->section_id,
                'class_name' => $schedule->schoolClass?->name ?? 'N/A',
            ])
            ->unique(fn ($pair) => $pair['class_id'] . '-' . $pair['section_id']);

        $resultUrl = DeepLinkService::generate('exam.result_published', [
            'exam_id' => $exam->id,
        ]);

        $total = 0;

        foreach ($classSectionPairs as $pair) {
            $query = Student::with(['guardians', 'schoolUser'])
                ->where('class_id', $pair['class_id'])
                ->where('status', 'enrolled');

            if ($pair['section_id']) {
                $query->where('section_id', $pair['section_id']);
            }

            $students = $query->get();

            foreach ($students as $student) {
                try {
                    $templateData = [
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'exam_title'   => $exam->name,
                        'class_name'   => $pair['class_name'],
                        'result_url'   => $resultUrl ?? '',
                        'school_name'  => $schoolName,
                    ];

                    // 1. Notify primary guardian via WhatsApp/SMS
                    $guardian = $student->guardians()
                        ->where('is_primary_contact', true)
                        ->first()
                        ?? $student->guardians()->first();

                    if ($guardian) {
                        $notificationService->sendFromTemplate(
                            templateSlug: 'exam.result_published',
                            notifiable:   $guardian,
                            data:         $templateData,
                            eventTrigger: 'exam.result_published',
                        );
                    }

                    // 2. In-app notification for student (if they have a portal account)
                    $studentUser = $student->schoolUser;
                    if ($studentUser) {
                        InAppNotification::create([
                            'user_id'    => $studentUser->id,
                            'title'      => 'Results Published',
                            'body'       => "{$exam->name} results for {$pair['class_name']} have been published.",
                            'type'       => 'success',
                            'action_url' => $resultUrl,
                        ]);
                    }

                    // 3. Direct WhatsApp/SMS to student
                    $notificationService->sendToStudentDirectly(
                        student:      $student,
                        title:        'Results Published',
                        body:         "Dear {$student->first_name}, {$exam->name} results for {$pair['class_name']} have been published. — {$schoolName}",
                        eventTrigger: 'exam.result_published',
                    );

                    $total++;
                } catch (\Throwable $e) {
                    Log::error("NotifyResultPublished: Failed for student {$student->id}: {$e->getMessage()}");
                }
            }
        }

        Log::info("NotifyResultPublished: Sent {$total} notification(s) for exam '{$exam->name}'.");
    }
}
