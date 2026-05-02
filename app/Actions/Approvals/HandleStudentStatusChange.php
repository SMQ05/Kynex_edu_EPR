<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Events\StudentDeactivated;
use App\Enums\StudentStatus;
use App\Models\ApprovalRequest;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\Student;

/**
 * HandleStudentStatusChange — Executed when a student_status_change approval is approved.
 *
 * 1. Updates the student's status, date, and reason.
 * 2. Fires StudentDeactivated event if leaving enrolled status.
 * 3. Notifies the requester via in-app notification.
 */
class HandleStudentStatusChange
{
    public function handle(ApprovalRequest $approval): void
    {
        $student = Student::findOrFail($approval->subject_id);
        $previousStatus = $student->status;

        // 1. Update student status
        $student->update([
            'status'               => $approval->payload['new_status'],
            'status_changed_at'    => $approval->payload['date'] ?? now(),
            'status_change_reason' => $approval->payload['reason'] ?? null,
        ]);

        // 2. Fire billing events if student is being deactivated
        $newStatus = $approval->payload['new_status'];
        if ($newStatus !== 'enrolled') {
            event(new StudentDeactivated(
                student: $student,
                previousStatus: $previousStatus instanceof StudentStatus
                    ? $previousStatus
                    : StudentStatus::Enrolled,
            ));
        }

        // 3. Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Status Change Approved',
            'body'    => "Status change for {$student->full_name} to '{$newStatus}' has been approved and applied.",
            'type'    => 'success',
        ]);
    }
}
