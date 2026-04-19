<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\InAppNotification;
use Illuminate\Support\Facades\Log;

/**
 * HandleExamMarkChange — Executed when an exam_mark_change approval is approved.
 *
 * Phase 10.3 — Updates exam marks with audit logging of old/new values.
 * Notifies the requester and the student's guardian.
 */
class HandleExamMarkChange
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload;

        $mark = ExamMark::where('exam_schedule_id', $payload['exam_schedule_id'] ?? null)
            ->where('student_id', $payload['student_id'])
            ->firstOrFail();

        $oldMarks = $mark->marks_obtained;

        // 1. Update marks
        $mark->update([
            'marks_obtained' => $payload['new_marks'],
        ]);

        // 2. Log the change with old/new values
        Log::info('HandleExamMarkChange: Marks updated', [
            'exam_mark_id' => $mark->id,
            'student_id'   => $payload['student_id'],
            'old_marks'    => $oldMarks,
            'new_marks'    => $payload['new_marks'],
            'reason'       => $payload['reason'] ?? 'N/A',
            'approved_by'  => $approval->reviewed_by_id,
        ]);

        // 3. Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Mark Change Approved',
            'body'    => "Marks changed from {$oldMarks} to {$payload['new_marks']} (Reason: {$payload['reason']}).",
            'type'    => 'success',
        ]);

        // 4. Notify student's guardian if linked
        $student = $mark->student;
        if ($student?->schoolUser) {
            InAppNotification::create([
                'user_id' => $student->schoolUser->id,
                'title'   => 'Exam Marks Updated',
                'body'    => "Your marks have been updated from {$oldMarks} to {$payload['new_marks']}.",
                'type'    => 'info',
            ]);
        }
    }
}
