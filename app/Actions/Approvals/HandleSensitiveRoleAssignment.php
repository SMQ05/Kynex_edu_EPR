<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;

/**
 * HandleSensitiveRoleAssignment — Executed when a sensitive_role_assignment
 * approval is approved.
 *
 * Phase 10.3 — Assigns or revokes a sensitive role (e.g. ACCOUNTANT, SCHOOL_ADMIN,
 * INSTITUTE_HEAD) after Institute Head approval.
 */
class HandleSensitiveRoleAssignment
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload;

        $user     = SchoolUser::findOrFail($payload['school_user_id']);
        $roleName = $payload['role_name'];
        $action   = $payload['action']; // 'assign' or 'revoke'

        if ($action === 'assign') {
            $user->assignRole($roleName);

            // Set as active role if user has no active role
            if (! $user->active_role) {
                $user->update(['active_role' => $roleName]);
            }

            $verb = 'assigned to';
        } else {
            $user->removeRole($roleName);

            // If revoked role was the active role, switch to next available
            if ($user->active_role === $roleName) {
                $nextRole = $user->roles->where('name', '!=', $roleName)->first()?->name ?? 'STUDENT';
                $user->update(['active_role' => $nextRole]);
            }

            $verb = 'revoked from';
        }

        // Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Role Change Approved',
            'body'    => "Role \"{$roleName}\" has been {$verb} {$user->name}.",
            'type'    => 'success',
        ]);

        // Notify affected user
        InAppNotification::create([
            'user_id' => $user->id,
            'title'   => 'Role Updated',
            'body'    => "Your role \"{$roleName}\" has been {$verb} your account." .
                         ($action === 'revoke' ? " Active role: {$user->active_role}" : ''),
            'type'    => $action === 'assign' ? 'success' : 'warning',
        ]);
    }
}
