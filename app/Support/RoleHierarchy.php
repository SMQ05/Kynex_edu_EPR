<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SchoolUser;

/**
 * RoleHierarchy — Defines numeric authority levels for every school-level role.
 *
 * Rules:
 *  - Higher number = higher authority.
 *  - An actor may only edit/delete/suspend/revoke roles on a target whose
 *    max level is strictly BELOW the actor's own max level.
 *  - Users with bypass_approvals permission are exempt from hierarchy checks.
 *  - SAAS_ONLY_ROLES can only be assigned/revoked by a SaasAdmin via the
 *    platform panel — they are invisible to all school-level actions.
 */
final class RoleHierarchy
{
    // ── Numeric level per role ───────────────────────────────────────
    public const LEVELS = [
        'MULTI_INSTITUTE_HEAD' => 110, // SaaS-assigned only — owner of multiple institutes
        'INSTITUTE_HEAD'       => 100, // School principal / institute head — set by SaaS admin only
        'SCHOOL_ADMIN'         => 90,  // IT / system admin
        'HR_MANAGER'           => 70,
        'REGISTRAR'            => 70,
        'BURSAR'               => 70,
        'EXAM_ADMIN'           => 70,
        'ACCOUNTANT'           => 60,
        'TEACHER'              => 50,
        'TRANSPORT_MANAGER'    => 40,
        'HOSTEL_WARDEN'        => 40,
        'LIBRARIAN'            => 40,
        'ATTENDANCE_CLERK'     => 40,
        'NURSE'                => 40,
        'COUNSELOR'            => 40,
        'CAFETERIA_MANAGER'    => 40,
        'RECEPTIONIST'         => 30,
        'PARENT'               => 20,
        'STUDENT'              => 10,
    ];

    /**
     * Roles that require bypass_approvals to assign or revoke via school panel.
     * All roles at level >= 70.
     */
    public const PROTECTED_ROLES = [
        'MULTI_INSTITUTE_HEAD',
        'INSTITUTE_HEAD',
        'SCHOOL_ADMIN',
        'HR_MANAGER',
        'REGISTRAR',
        'BURSAR',
        'EXAM_ADMIN',
    ];

    /**
     * Roles that can ONLY be assigned/revoked by a SaasAdmin via the platform panel.
     * School-level users — even INSTITUTE_HEAD — cannot assign these.
     */
    public const SAAS_ONLY_ROLES = [
        'MULTI_INSTITUTE_HEAD',
        'INSTITUTE_HEAD',
    ];

    // ── Public API ───────────────────────────────────────────────────

    /**
     * Get the numeric authority level for a single role name.
     * Returns 0 for unknown roles.
     */
    public static function levelOfRole(string $role): int
    {
        return self::LEVELS[$role] ?? 0;
    }

    /**
     * Get the highest authority level held by a SchoolUser
     * (across all their assigned roles).
     */
    public static function levelOf(SchoolUser $user): int
    {
        $max = 0;
        foreach ($user->roles as $role) {
            $level = self::LEVELS[$role->name] ?? 0;
            if ($level > $max) {
                $max = $level;
            }
        }
        return $max;
    }

    /**
     * True if the role name is in the protected list.
     */
    public static function isProtected(string $role): bool
    {
        return in_array($role, self::PROTECTED_ROLES, true);
    }

    /**
     * True if the role can only be assigned by the SaaS platform admin.
     */
    public static function isSaasOnly(string $role): bool
    {
        return in_array($role, self::SAAS_ONLY_ROLES, true);
    }

    /**
     * Determines whether $actor is permitted to act on $target.
     *
     * Returns true when EITHER:
     *  a) the actor has bypass_approvals permission, OR
     *  b) the actor's max role level is strictly greater than the target's.
     *
     * Returning false means the action requires an approval request or is
     * blocked outright.
     */
    public static function canAct(?SchoolUser $actor, SchoolUser $target): bool
    {
        if ($actor === null) {
            return false;
        }

        // Users with bypass_approvals can always act
        if ($actor->hasPermissionTo('bypass_approvals')) {
            return true;
        }

        return self::levelOf($actor) > self::levelOf($target);
    }

    /**
     * True if $actor is allowed to assign the given role to someone.
     *
     * SaaS-only roles always return false — they must be set from the
     * SaaS admin panel.
     *
     * For other protected roles, the actor must have bypass_approvals
     * (immediate assignment) or submit for approval.
     * This helper returns true only for the "immediate" path.
     */
    public static function canAssignDirectly(?SchoolUser $actor, string $role): bool
    {
        if ($actor === null || self::isSaasOnly($role)) {
            return false;
        }

        return $actor->hasPermissionTo('bypass_approvals');
    }
}
