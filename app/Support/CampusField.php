<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant\Campus;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;

/**
 * Centralised builder for the "Campus" form field.
 *
 * Behaviour:
 *  - 0 or 1 campus on the tenant → render as a hidden field with the
 *    only campus pre-selected. The school admin doesn't need to pick.
 *  - 2+ campuses, user has a campus_id assigned → Select pre-filled and
 *    locked unless the user is institute-level (Multi-Institute Head /
 *    Institute Head / SaaS admin), who can place rows at any campus.
 *  - 2+ campuses, user is institute-level → free Select, not required.
 *
 * Use this helper everywhere a `campus_id` form field is needed instead
 * of hand-rolling the Select / required / disabled rules per resource.
 */
final class CampusField
{
    /**
     * Build the right field based on how many campuses exist and whether
     * the actor is locked to one campus.
     *
     * @param  bool  $required  When true, the rendered Select is marked
     *                         required. Hidden fallback always carries
     *                         the campus id, so this is mostly cosmetic.
     */
    public static function make(bool $required = true): Hidden|Select
    {
        $count = Campus::query()->count();
        $canChooseAny = self::canChooseAnyCampus();
        $scopedId = self::scopedCampusId();

        // 0 or 1 campus on the tenant → no choice to offer.
        if ($count <= 1) {
            $only = Campus::query()->value('id');
            return Hidden::make('campus_id')->default($only);
        }

        // Multiple campuses exist, but the actor is locked to one and
        // can't roam (school admin / non-institute role) → hide the
        // field and default to their own campus. They have no choice
        // to make, so showing a disabled Select adds noise.
        if ($scopedId && ! $canChooseAny) {
            return Hidden::make('campus_id')->default($scopedId);
        }

        return Select::make('campus_id')
            ->label('Campus')
            ->relationship('campus', 'name')
            ->searchable()
            ->preload()
            ->required($required)
            ->default(fn () => $scopedId)
            ->dehydrated(true);
    }

    /**
     * The campus the current actor is locked to. Returns null for users
     * who can manage rows across every campus (Institute Head,
     * Multi-Institute Head, SaaS Admin).
     */
    public static function scopedCampusId(): ?string
    {
        $user = auth('school_users')->user() ?? auth()->user();
        if (! $user) {
            return null;
        }

        if (self::canChooseAnyCampus()) {
            return null;
        }

        return $user->campus_id ?? null;
    }

    /**
     * True for institute-level roles, who can pick any campus.
     */
    public static function canChooseAnyCampus(): bool
    {
        $user = auth('school_users')->user() ?? auth()->user();
        if (! $user) {
            return false;
        }

        $role = (string) ($user->active_role ?? $user->roles->first()?->name ?? '');
        return in_array($role, ['MULTI_INSTITUTE_HEAD', 'INSTITUTE_HEAD'], true);
    }
}
