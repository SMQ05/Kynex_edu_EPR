<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;

/**
 * HandleRoleRevocation — Executed when a role_revocation approval is approved.
 *
 * 1. Removes the specified role from the user.
 * 2. Updates active_role if the revoked role was the active one.
 * 3. Falls back to STUDENT role if no roles remain.
 * 4. Notifies the affected user via in-app notification.
 */
class HandleRoleRevocation
{
    public function handle(ApprovalRequest $approval): void
    {
        $user = SchoolUser::findOrFail($approval->subject_id);
        $roleName = $approval->payload['role_name'];

        $this->revokeRole($user, $roleName);

        // Notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Role Revocation Approved',
            'body'    => "The {$roleName} role has been revoked from {$user->name}.",
            'type'    => 'success',
        ]);
    }

    /**
     * Revoke a role from a user with proper fallback logic.
     */
    public static function revokeRole(SchoolUser $user, string $roleName): void
    {
        // 1. Check user has more than this one role
        $remainingRoles = $user->roles
            ->where('name', '!=', $roleName);

        // 2. Revoke only the specified role
        $user->removeRole($roleName);

        // 3. Update active_role if it was the active one
        if ($user->active_role === $roleName) {
            $user->update([
                'active_role' => $remainingRoles->first()?->name,
            ]);
        }

        // 4. If no roles remain, assign STUDENT as default
        if ($user->roles()->count() === 0) {
            $user->assignRole('STUDENT');
            $user->update(['active_role' => 'STUDENT']);
        }

        // 5. Notify user via in_app
        InAppNotification::create([
            'user_id' => $user->id,
            'title'   => 'Role Updated',
            'body'    => "Your {$roleName} role has been removed. Active role: {$user->fresh()->active_role}",
            'type'    => 'warning',
        ]);
    }
}
