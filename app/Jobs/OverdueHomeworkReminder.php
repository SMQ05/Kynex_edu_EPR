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
 * OverdueHomeworkReminder — Sends reminders for homework that is past due date
 * but has no submission from the student yet.
 *
 * Part 4f — Scheduled daily (via console kernel) to run at a configurable time.
 * Notifies guardians once per overdue homework using 'homework.overdue' template.
 */
class OverdueHomeworkReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function handle(NotificationService $notificationService): void
    {
        // Find homework that is 1+ days overdue
        $overdueHomework = HomeworkAssignment::with(['schoolClass', 'section', 'subject', 'teacher'])
            ->where('due_date', '<', now()->toDateString())
            ->whereDate('due_date', '>=', now()->subDays(7)->toDateString()) // Only last 7 days to avoid spam
            ->get();

        if ($overdueHomework->isEmpty()) {
            return;
        }

        $schoolName = config('app.name', 'School');
        $total      = 0;

        foreach ($overdueHomework as $homework) {
            // Get students who haven't submitted
            $submittedStudentIds = $homework->submissions()->pluck('student_id');

            $query = Student::with(['guardians'])
                ->where('class_id', $homework->class_id)
                ->where('status', 'enrolled')
                ->whereNotIn('id', $submittedStudentIds);

            if ($homework->section_id) {
                $query->where('section_id', $homework->section_id);
            }

            $studentsWithoutSubmission = $query->get();

            foreach ($studentsWithoutSubmission as $student) {
                try {
                    $guardian = $student->guardians()
                        ->where('is_primary_contact', true)
                        ->first()
                        ?? $student->guardians()->first();

                    if (! $guardian) {
                        continue;
                    }

                    $notificationService->sendFromTemplate(
                        templateSlug: 'homework.overdue',
                        notifiable:   $guardian,
                        data: [
                            'student_name' => $student->first_name . ' ' . $student->last_name,
                            'class'        => $homework->schoolClass?->name ?? 'N/A',
                            'subject'      => $homework->subject?->name ?? 'N/A',
                            'homework'     => $homework->title,
                            'due_date'     => $homework->due_date->format('M d, Y'),
                            'days_overdue' => now()->diffInDays($homework->due_date),
                            'school_name'  => $schoolName,
                        ],
                        eventTrigger: 'homework.overdue',
                    );

                    $total++;

                } catch (\Throwable $e) {
                    Log::error("OverdueHomeworkReminder: Failed for student {$student->id}: {$e->getMessage()}");
                }
            }
        }

        Log::info("OverdueHomeworkReminder: Sent {$total} overdue reminder(s).");
    }
}
