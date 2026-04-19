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

        // SaaS Admin impersonation / Institute Head bypass — always show
        if ($user->hasRole(['institute_head', 'multi_institute_head', 'school_admin'])) {
            // Still enforce for school_admin below
            if ($user->hasRole(['institute_head', 'multi_institute_head'])) {
                return true;
            }
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

        // If a write permission is defined, use it for mutation checks
        if (! empty(static::$rbacWritePermission)) {
            return $user->hasPermissionTo(static::$rbacWritePermission);
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
     */
    protected static function currentUserCanAccess(): bool
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return false;
        }

        // Institute Head / Multi-IH always have access within their school
        if ($user->hasRole(['institute_head', 'multi_institute_head'])) {
            return true;
        }

        // No permission defined on this resource → deny by default
        if (empty(static::$rbacPermission ?? null)) {
            return false;
        }

        return $user->hasPermissionTo(static::$rbacPermission);
    }
}
