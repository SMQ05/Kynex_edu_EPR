<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;

/**
 * HandleStaffDelete — Executed when a staff_delete approval is approved.
 *
 * Phase 10.3 — Deactivates the SchoolUser, revokes all roles, and notifies both parties.
 */
class HandleStaffDelete
{
    public function handle(ApprovalRequest $approval): void
    {
        $user = SchoolUser::findOrFail($approval->payload['school_user_id']);
        $userName = $user->name;

        // 1. Revoke all roles and deactivate
        $user->syncRoles([]);
        $user->update([
            'is_active'   => false,
            'active_role' => null,
        ]);

        // 2. Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Staff Removal Approved',
            'body'    => "Request to remove staff member \"{$userName}\" has been approved. Account deactivated and all roles revoked.",
            'type'    => 'success',
        ]);

        // 3. Notify the affected staff member
        InAppNotification::create([
            'user_id' => $user->id,
            'title'   => 'Account Deactivated',
            'body'    => "Your account has been deactivated. Reason: " . ($approval->payload['reason'] ?? 'N/A'),
            'type'    => 'danger',
        ]);
    }
}
