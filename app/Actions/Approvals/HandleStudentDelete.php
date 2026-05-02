<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\Student;
use Illuminate\Support\Facades\Log;

/**
 * HandleStudentDelete — Executed when a student_delete approval is approved.
 *
 * Phase 10.3 — Soft-deletes the student record, revokes roles, and anonymizes PII.
 * HARD delete is never performed.
 */
class HandleStudentDelete
{
    public function handle(ApprovalRequest $approval): void
    {
        $student = Student::findOrFail($approval->payload['student_id']);
        $studentName = $student->full_name;

        // 1. Anonymize PII before soft-delete
        $student->update([
            'first_name'  => 'Deleted',
            'last_name'   => 'Student [' . $student->id . ']',
            'phone'       => null,
            'email'       => null,
            'date_of_birth' => null,
            'address'     => null,
            'city'        => null,
            'medical_notes'      => null,
            'special_needs_notes' => null,
        ]);

        // 2. Revoke all Spatie roles from the linked SchoolUser (if exists)
        if ($student->schoolUser) {
            $student->schoolUser->syncRoles([]);
            $student->schoolUser->update([
                'active_role' => null,
                'is_active'   => false,
            ]);
        }

        // 3. Soft-delete the student record
        $student->delete();

        // 4. Log the deletion
        Log::info("HandleStudentDelete: Student {$studentName} ({$student->id}) soft-deleted and PII anonymized.", [
            'reason'       => $approval->payload['reason'] ?? 'N/A',
            'approved_by'  => $approval->reviewed_by_id,
        ]);

        // 5. Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Student Deletion Approved',
            'body'    => "Deletion of student \"{$studentName}\" has been approved. Record soft-deleted and PII anonymized.",
            'type'    => 'success',
        ]);
    }
}
