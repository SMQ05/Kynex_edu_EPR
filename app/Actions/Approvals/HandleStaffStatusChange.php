<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;

/**
 * HandleStaffStatusChange — Executed when a staff_status_change approval is approved.
 *
 * 1. Deactivates the staff user (is_active = false).
 * 2. Notifies the requester via in-app notification.
 */
class HandleStaffStatusChange
{
    public function handle(ApprovalRequest $approval): void
    {
        $user = SchoolUser::findOrFail($approval->subject_id);

        // 1. Deactivate the staff user
        $user->update([
            'is_active' => false,
        ]);

        // 2. Notify requester
        $action = $approval->payload['action'] ?? 'suspend';

        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Staff Status Change Approved',
            'body'    => "The request to {$action} {$user->name} has been approved and applied.",
            'type'    => 'success',
        ]);

        // 3. Notify the affected staff member
        InAppNotification::create([
            'user_id' => $user->id,
            'title'   => 'Account Status Updated',
            'body'    => "Your account has been deactivated. Reason: " . ($approval->payload['reason'] ?? 'N/A'),
            'type'    => 'danger',
        ]);
    }
}
