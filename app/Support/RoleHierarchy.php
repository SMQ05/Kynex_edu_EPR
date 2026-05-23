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
     * Roles that can ONLY be assigned for the very FIRST TIME by a SaasAdmin via the
     * platform panel. Once a holder exists, the school-side elevated-role workflow
     * (dual-approval) takes over for reassignment.
     *
     * Do NOT remove entries from here without updating the school-panel assign logic.
     */
    public const SAAS_ONLY_ROLES = [
        'MULTI_INSTITUTE_HEAD',
        'INSTITUTE_HEAD',
    ];

    /**
     * Roles that require dual approval (school head + SaaS admin, either one suffices)
     * when being reassigned from the school panel after the first-time SaaS assignment.
     *
     * Key   = role name
     * Value = minimum actor level required to *submit* the request from the school panel.
     *         INSTITUTE_HEAD (100) → any SCHOOL_ADMIN (90) or above may submit.
     *         MULTI_INSTITUTE_HEAD (110) → only INSTITUTE_HEAD (100) or above may submit.
     */
    public const DUAL_APPROVAL_SUBMIT_LEVEL = [
        'INSTITUTE_HEAD'       => 90,   // SCHOOL_ADMIN and above
        'MULTI_INSTITUTE_HEAD' => 100,  // INSTITUTE_HEAD and above
    ];

    /**
     * approver_level value used in ApprovalRequest for each dual-approval role.
     */
    public const DUAL_APPROVAL_LEVEL = [
        'INSTITUTE_HEAD'       => 'institute_head_or_saas',
        'MULTI_INSTITUTE_HEAD' => 'multi_head_or_saas',
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
     * True if the role's FIRST-TIME assignment must come from the SaaS platform admin.
     * Once a holder exists the school-panel dual-approval workflow applies.
     */
    public static function isSaasOnly(string $role): bool
    {
        return in_array($role, self::SAAS_ONLY_ROLES, true);
    }

    /**
     * True if this role uses the dual-approval (school head OR SaaS) workflow
     * when submitted from the school panel (only applies to reassignment, not
     * first-time assignment which remains SaaS-only).
     */
    public static function isDualApproval(string $role): bool
    {
        return array_key_exists($role, self::DUAL_APPROVAL_SUBMIT_LEVEL);
    }

    /**
     * Minimum actor level required to SUBMIT a dual-approval request for this role.
     * Returns PHP_INT_MAX for roles that are not in the dual-approval list.
     */
    public static function dualApprovalSubmitLevel(string $role): int
    {
        return self::DUAL_APPROVAL_SUBMIT_LEVEL[$role] ?? PHP_INT_MAX;
    }

    /**
     * The approver_level string to store on ApprovalRequest for a dual-approval role.
     */
    public static function dualApprovalLevel(string $role): string
    {
        return self::DUAL_APPROVAL_LEVEL[$role] ?? 'institute_head';
    }

    /**
     * Which school-panel approver levels a given role can approve.
     * Returns the set of approver_level values the $user is authorised to approve.
     */
    public static function approvableLevels(SchoolUser $user): array
    {
        $baseLevel = self::levelOf($user);

        $levels = ['institute_head']; // SCHOOL_ADMIN and above always see this

        if ($baseLevel >= self::LEVELS['INSTITUTE_HEAD']) {
            $levels[] = 'institute_head_or_saas'; // INSTITUTE_HEAD can approve these
        }

        if ($baseLevel >= self::LEVELS['MULTI_INSTITUTE_HEAD']) {
            $levels[] = 'multi_head_or_saas'; // only MULTI_INSTITUTE_HEAD can approve these
        }

        return $levels;
    }

    /**
     * Determines whether $actor is permitted to act on $target.
     *
     * Rules:
     *   - Strictly higher level → always allowed (e.g. INSTITUTE_HEAD acting
     *     on SCHOOL_ADMIN).
     *   - Equal level → allowed ONLY if the actor has bypass_approvals
     *     (e.g. a peer admin overseeing another peer in extraordinary cases).
     *   - Lower level → never allowed, even with bypass_approvals. This is
     *     critical: bypass_approvals exists to skip approval workflows, not
     *     to grant level-punching rights. A SCHOOL_ADMIN must NOT be able
     *     to edit an INSTITUTE_HEAD just because they hold bypass.
     *
     * Returning false means the action requires an approval request (for
     * dual-approval roles) or is blocked outright.
     */
    public static function canAct(?SchoolUser $actor, SchoolUser $target): bool
    {
        if ($actor === null) {
            return false;
        }

        $actorLevel  = self::levelOf($actor);
        $targetLevel = self::levelOf($target);

        if ($actorLevel > $targetLevel) {
            return true;
        }

        if ($actorLevel === $targetLevel && $actor->hasPermissionTo('bypass_approvals')) {
            return true;
        }

        return false;
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
