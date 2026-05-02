<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;
use Illuminate\Support\Facades\Log;

/**
 * HandleStaffHire — runs when an institute head approves a new staff
 * (teacher / admin / clerk / etc.) hire request submitted by a school
 * admin or HR manager. Activates the SchoolUser and notifies the
 * requester.
 */
class HandleStaffHire
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload ?? [];
        $userId = $payload['school_user_id'] ?? null;
        if (! $userId) {
            return;
        }

        $user = SchoolUser::find($userId);
        if (! $user) {
            Log::warning('HandleStaffHire: school_user missing', [
                'approval_id' => $approval->id,
                'user_id'     => $userId,
            ]);
            return;
        }

        $user->forceFill([
            'is_active'         => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->saveQuietly();

        if (! empty($payload['assign_role'])) {
            $role = $payload['assign_role'];
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
            if (! $user->active_role) {
                $user->update(['active_role' => $role]);
            }
        }

        if ($approval->requested_by_id) {
            InAppNotification::create([
                'user_id' => $approval->requested_by_id,
                'title'   => 'Staff hire approved',
                'body'    => "{$user->name} has been activated.",
                'type'    => 'success',
            ]);
        }
    }
}
