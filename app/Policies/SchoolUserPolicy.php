<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolUser;
use App\Support\RoleHierarchy;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * SchoolUserPolicy — Controls who can create, view, edit, and delete
 * SchoolUser records in the admin panel.
 *
 * Core rule: you may only act on users whose authority level is
 * strictly below your own — unless you hold bypass_approvals.
 */
class SchoolUserPolicy
{
    /**
     * Only users with view_staff permission may access the list.
     */
    public function viewAny(SchoolUser $actor): bool
    {
        return $this->hasPermission($actor, 'view_staff');
    }

    /**
     * Viewing a single record requires view_staff AND the actor must
     * outrank the target (or hold bypass_approvals).
     */
    public function view(SchoolUser $actor, SchoolUser $target): bool
    {
        if (! $this->hasPermission($actor, 'view_staff')) {
            return false;
        }

        // Allow viewing your own profile always
        if ($actor->is($target)) {
            return true;
        }

        return RoleHierarchy::canAct($actor, $target);
    }

    /**
     * Creating users is allowed for staff creators and user managers.
     */
    public function create(SchoolUser $actor): bool
    {
        return $this->hasPermission($actor, 'create_staff')
            || $this->hasPermission($actor, 'system.user_manage');
    }

    /**
     * Editing a user: actor must outrank the target.
     */
    public function update(SchoolUser $actor, SchoolUser $target): bool
    {
        if (! $this->hasPermission($actor, 'edit_staff')) {
            return false;
        }

        // Always allow editing your own profile
        if ($actor->is($target)) {
            return true;
        }

        return RoleHierarchy::canAct($actor, $target);
    }

    /**
     * Deleting a user: actor must outrank the target.
     * (INSTITUTE_HEAD and above can never be deleted from the school panel;
     * they must be removed by the SaaS admin.)
     */
    public function delete(SchoolUser $actor, SchoolUser $target): bool
    {
        if (! $this->hasPermission($actor, 'delete_staff')) {
            return false;
        }

        // Cannot delete your own account
        if ($actor->is($target)) {
            return false;
        }

        // SAAS_ONLY roles can only be removed by the platform admin
        foreach ($target->roles as $role) {
            if (RoleHierarchy::isSaasOnly($role->name)) {
                return false;
            }
        }

        return RoleHierarchy::canAct($actor, $target);
    }

    /**
     * Permission check that returns false when the permission slug isn't seeded
     * in the current tenant DB. Older tenants may pre-date newer permissions —
     * we'd rather hide an action than throw a 500.
     */
    private function hasPermission(SchoolUser $actor, string $permission): bool
    {
        try {
            return $actor->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
