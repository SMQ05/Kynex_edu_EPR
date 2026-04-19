<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\InAppNotification;
use Illuminate\Support\Facades\Log;

/**
 * HandleAssignmentChange — Executed when an assignment_change approval is approved.
 *
 * Phase 10.3 — Updates a specific field on a HomeworkAssignment after approval.
 * Notifies the requester and affected students.
 */
class HandleAssignmentChange
{
    /** Fields that may be updated via the approval workflow. */
    private const ALLOWED_FIELDS = [
        'title',
        'description',
        'due_date',
        'attachment_path',
    ];

    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload;

        $homework = HomeworkAssignment::findOrFail($payload['homework_id']);

        $field    = $payload['field_changed'];
        $oldValue = $payload['old_value'];
        $newValue = $payload['new_value'];

        // 1. Validate the field is allowed
        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            Log::warning('HandleAssignmentChange: Blocked update to disallowed field', [
                'homework_id' => $payload['homework_id'],
                'field'       => $field,
                'approved_by' => $approval->reviewed_by_id,
            ]);

            throw new \InvalidArgumentException("Field '{$field}' is not allowed for assignment changes.");
        }

        // 2. Update the specified field
        $homework->update([
            $field => $newValue,
        ]);

        // 3. Log the change
        Log::info('HandleAssignmentChange: Assignment updated', [
            'homework_id'   => $homework->id,
            'field_changed' => $field,
            'old_value'     => $oldValue,
            'new_value'     => $newValue,
            'reason'        => $payload['reason'] ?? 'N/A',
            'approved_by'   => $approval->reviewed_by_id,
        ]);

        // 4. Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Assignment Change Approved',
            'body'    => "Change to \"{$homework->title}\" ({$field}) has been approved.",
            'type'    => 'success',
        ]);
    }
}
