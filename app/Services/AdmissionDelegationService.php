<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchoolUser;
use App\Models\Tenant\AdmissionDelegation;
use Illuminate\Support\Facades\DB;

/**
 * Authorisation surface for class-scoped admission delegations.
 *
 *   userHasPermission()        — does this user hold the permission at any scope?
 *   allowedClassIdsForUser()   — null = unlimited, [] = none, [ids] = limited.
 *   syncForUser()              — bulk replace one user's delegations.
 *
 * Three role-bypasses are honoured throughout: SCHOOL_ADMIN,
 * INSTITUTE_HEAD, MULTI_INSTITUTE_HEAD always have unlimited scope.
 *
 * The umbrella permission `manage_student_admissions` implicitly covers
 * the three specific permissions for the same class scope. So a row for
 * (user, manage_student_admissions, class 5) grants enter_admission_marks,
 * conduct_admission_interview, and create_admission_tests for class 5.
 */
class AdmissionDelegationService
{
    public const PERMISSIONS = [
        'enter_admission_marks',
        'conduct_admission_interview',
        'create_admission_tests',
        'manage_student_admissions',
    ];

    /**
     * True if the user holds the permission anywhere — direct grant, role
     * grant, or admission_delegations row (for the permission itself or
     * the umbrella `manage_student_admissions`).
     */
    public function userHasPermission(?SchoolUser $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }
        if ($this->hasBypassRole($user)) {
            return true;
        }
        try {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        } catch (\Throwable) {
            // Permission table may not have this slug yet — fall through.
        }

        return AdmissionDelegation::query()
            ->where('school_user_id', $user->id)
            ->whereIn('permission', $this->coveringPermissions($permission))
            ->exists();
    }

    /**
     * Class IDs the user is allowed to act on for the given permission.
     *
     *   null    — unlimited (admin role, role-based grant, or any
     *             delegation row with class_id IS NULL)
     *   []      — no access at all
     *   [ids…]  — strictly these classes
     */
    public function allowedClassIdsForUser(?SchoolUser $user, string $permission): ?array
    {
        if (! $user) {
            return [];
        }
        if ($this->hasBypassRole($user)) {
            return null;
        }
        try {
            if ($user->hasPermissionTo($permission)) {
                return null;
            }
        } catch (\Throwable) {
            // ignore
        }

        $rows = AdmissionDelegation::query()
            ->where('school_user_id', $user->id)
            ->whereIn('permission', $this->coveringPermissions($permission))
            ->pluck('class_id')
            ->all();

        if (empty($rows)) {
            return [];
        }
        if (in_array(null, $rows, true)) {
            return null;
        }
        return array_values(array_unique(array_filter($rows)));
    }

    /**
     * Combined class scope across every admission permission. Used by the
     * Admissions list page so a delegated user sees every applicant in
     * any class they have ANY admission role on.
     */
    public function combinedAllowedClassIds(?SchoolUser $user): ?array
    {
        if (! $user) {
            return [];
        }
        if ($this->hasBypassRole($user)) {
            return null;
        }

        // If they have an unscoped role grant for any of the four
        // permissions, they're unlimited.
        foreach (self::PERMISSIONS as $perm) {
            try {
                if ($user->hasPermissionTo($perm)) {
                    return null;
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        $rows = AdmissionDelegation::query()
            ->where('school_user_id', $user->id)
            ->whereIn('permission', self::PERMISSIONS)
            ->pluck('class_id')
            ->all();

        if (empty($rows)) {
            return [];
        }
        if (in_array(null, $rows, true)) {
            return null;
        }
        return array_values(array_unique(array_filter($rows)));
    }

    /**
     * Has the user been granted ANY admission permission via either
     * Spatie or admission_delegations? Used to decide whether to show
     * admission resources to non-admin staff.
     */
    public function userHasAnyAdmissionGrant(?SchoolUser $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($this->hasBypassRole($user)) {
            return true;
        }
        foreach (self::PERMISSIONS as $perm) {
            if ($this->userHasPermission($user, $perm)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Replace this user's delegations atomically.
     *
     * @param  array<string, array{granted:bool, allClasses:bool, classIds:array<int,string>}>  $config
     *         Keyed by permission name.
     */
    public function syncForUser(SchoolUser $user, array $config): void
    {
        DB::transaction(function () use ($user, $config) {
            // Wipe the four managed permissions for this user; re-insert below.
            AdmissionDelegation::where('school_user_id', $user->id)
                ->whereIn('permission', self::PERMISSIONS)
                ->delete();

            foreach (self::PERMISSIONS as $perm) {
                $row = $config[$perm] ?? null;
                if (! $row || empty($row['granted'])) {
                    continue;
                }
                if (! empty($row['allClasses'])) {
                    AdmissionDelegation::create([
                        'school_user_id' => $user->id,
                        'permission'     => $perm,
                        'class_id'       => null,
                    ]);
                    continue;
                }
                foreach (array_unique((array) ($row['classIds'] ?? [])) as $classId) {
                    if (blank($classId)) continue;
                    AdmissionDelegation::create([
                        'school_user_id' => $user->id,
                        'permission'     => $perm,
                        'class_id'       => $classId,
                    ]);
                }
            }
        });

        $user->forgetCachedPermissions();
    }

    /**
     * Read back the current delegation state for the user, in the same
     * shape syncForUser() expects on input. Suitable for filling the
     * delegations form.
     *
     * @return array<string, array{granted:bool, allClasses:bool, classIds:array<int,string>}>
     */
    public function loadForUser(SchoolUser $user): array
    {
        $rows = AdmissionDelegation::query()
            ->where('school_user_id', $user->id)
            ->whereIn('permission', self::PERMISSIONS)
            ->get(['permission', 'class_id']);

        $out = [];
        foreach (self::PERMISSIONS as $perm) {
            $matching = $rows->where('permission', $perm);
            $allClasses = $matching->contains(fn ($r) => $r->class_id === null);
            $out[$perm] = [
                'granted'    => $matching->isNotEmpty(),
                'allClasses' => $allClasses,
                'classIds'   => $allClasses
                    ? []
                    : $matching->pluck('class_id')->filter()->values()->all(),
            ];
        }
        return $out;
    }

    protected function hasBypassRole(SchoolUser $user): bool
    {
        return $user->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']);
    }

    /**
     * For the given permission, return the list of permission slugs that
     * cover it. The umbrella `manage_student_admissions` covers all three
     * specific permissions; everything else covers only itself.
     */
    protected function coveringPermissions(string $permission): array
    {
        if ($permission === 'manage_student_admissions') {
            return [$permission];
        }
        return [$permission, 'manage_student_admissions'];
    }
}
