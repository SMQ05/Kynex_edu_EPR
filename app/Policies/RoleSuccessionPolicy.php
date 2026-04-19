<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolUser;
use App\Models\Tenant\RoleSuccession;

/**
 * RoleSuccessionPolicy — Authorization for role succession requests.
 *
 * Combined with RBAC permissions:
 *  - `initiate_role_succession` permission → who can start succession
 *  - `approve_role_succession` permission → who can approve standard succession
 *  - `request_institute_head_assignment` → who can request IH succession (escalates to SaaS Admin)
 */
class RoleSuccessionPolicy
{
    /**
     * School Admin can initiate succession for any non-admin role.
     * Institute Head can initiate succession for any role including School Admin.
     */
    public function initiate(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('initiate_role_succession');
    }

    /**
     * Only Institute Head (or Multi-IH) can approve standard succession requests.
     * SaaS Admin handles IH-level succession outside this policy.
     */
    public function approve(SchoolUser $user, RoleSuccession $succession): bool
    {
        // Institute Head succession must be approved by SaaS Admin — handled elsewhere
        if ($succession->needsSaasAdminApproval()) {
            return false; // Deny at school level
        }

        return $user->hasPermissionTo('approve_role_succession');
    }

    /**
     * School Admin or Institute Head can update the incoming user selection.
     */
    public function assignIncoming(SchoolUser $user, RoleSuccession $succession): bool
    {
        if (! $succession->isPending()) {
            return false;
        }

        return $user->hasPermissionTo('initiate_role_succession')
            || $user->hasPermissionTo('approve_role_succession');
    }

    /**
     * The requester or Institute Head can cancel a pending succession.
     */
    public function cancel(SchoolUser $user, RoleSuccession $succession): bool
    {
        if (! $succession->isPending()) {
            return false;
        }

        // Requester can cancel their own request
        if ($succession->requested_by === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('approve_role_succession');
    }

    /**
     * Institute Head and School Admin can view succession history.
     */
    public function view(SchoolUser $user): bool
    {
        return $user->hasAnyPermission([
            'initiate_role_succession',
            'approve_role_succession',
        ]);
    }
}
