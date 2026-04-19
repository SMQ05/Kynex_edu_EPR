<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * SaasAdmin — Central platform administrator.
 *
 * These are the people who manage the multi-tenant SaaS platform
 * (superadmins and support staff), NOT school-level users.
 *
 * @property string $id         ULID primary key
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role       superadmin | support
 * @property bool   $is_active
 * @property \Carbon\Carbon|null $last_login_at
 */
class SaasAdmin extends Authenticatable implements FilamentUser
{
    use HasUlids, SoftDeletes;

    // ── Table & Key ────────────────────────────────────────────
    protected $table = 'saas_admins';

    // ── Mass Assignment ────────────────────────────────────────
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    // ── Hidden (API / serialisation) ───────────────────────────
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ── Casts ──────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'password'      => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    // ── Filament Access ────────────────────────────────────────

    /**
     * Determine whether this admin can access the given Filament panel.
     *
     * Access is granted only when:
     *  1. The panel uses the `saas_admin` auth guard, AND
     *  2. The admin account is active.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getAuthGuard() === 'saas_admin'
            && $this->is_active === true;
    }
}
