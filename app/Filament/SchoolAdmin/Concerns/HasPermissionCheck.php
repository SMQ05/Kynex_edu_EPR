<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Concerns;

/**
 * HasPermissionCheck — Gate-checks for Filament Resources and Pages.
 *
 * Add this trait to any Resource or Page and define:
 *
 *   protected static string $rbacPermission = 'the_permission_slug';
 *
 * The trait wires up:
 *  - shouldRegisterNavigation() → hides the nav item when user lacks permission
 *  - canAccess()                → blocks direct URL access (Pages)
 *  - canViewAny()               → blocks list page (Resources)
 *  - canCreate()                → blocks create (uses same or _create suffix)
 *  - canEdit()                  → blocks edit
 *  - canDelete()                → blocks delete
 *
 * For resources that use a separate write permission, define:
 *   protected static string $rbacWritePermission = 'create_student';
 */
trait HasPermissionCheck
{
    // ── Navigation visibility ─────────────────────────────────────

    /**
     * Hide from sidebar when user lacks the required permission.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return false;
        }

        // School Admin / Institute Head / Multi-IH bypass — always show
        if ($user->hasRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'SCHOOL_ADMIN'])) {
            return true;
        }

        return static::currentUserCanAccess();
    }

    // ── Resource authorization gates ──────────────────────────────

    /** Block list page access. */
    public static function canViewAny(): bool
    {
        return static::currentUserCanAccess();
    }

    /** Block create page access. */
    public static function canCreate(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        // School Admin, Institute Head, Multi-IH always have full access
        if ($user->hasRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'SCHOOL_ADMIN'])) {
            return true;
        }

        // If a write permission is defined, use it for mutation checks
        if (! empty(static::$rbacWritePermission)) {
            try {
                return $user->hasPermissionTo(static::$rbacWritePermission);
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                return false;
            }
        }

        return static::currentUserCanAccess();
    }

    /** Block edit access. */
    public static function canEdit($record): bool
    {
        return static::canCreate();
    }

    /** Block delete access. */
    public static function canDelete($record): bool
    {
        return static::canCreate();
    }

    // ── Page authorization gate ───────────────────────────────────

    /** Block direct URL access to Pages. */
    public static function canAccess(): bool
    {
        return static::currentUserCanAccess();
    }

    // ── Core check ────────────────────────────────────────────────

    /**
     * Returns true if the authenticated school user has the required permission.
     *
     * Institute Head and Multi-Institute Head bypass all resource-level checks
     * (they have full access within their school).
     *
     * If both $rbacPermission (read) and $rbacWritePermission (write) are
     * defined, holding *either* grants visibility — a teacher who can write
     * homework should still see it in the navigation even if they lack the
     * matching read permission.
     */
    protected static function currentUserCanAccess(): bool
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return false;
        }

        // School Admin, Institute Head, Multi-IH always have access within their school
        if ($user->hasRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'SCHOOL_ADMIN'])) {
            return true;
        }

        $candidates = array_filter([
            property_exists(static::class, 'rbacPermission') ? static::$rbacPermission : null,
            property_exists(static::class, 'rbacWritePermission') ? static::$rbacWritePermission : null,
        ]);

        if (empty($candidates)) {
            return false;
        }

        foreach ($candidates as $permission) {
            try {
                if ($user->hasPermissionTo($permission)) {
                    return true;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                // Permission missing in tenant DB (older tenant predating a new
                // slug). Fall through and try the next candidate.
                continue;
            }
        }

        return false;
    }
}
