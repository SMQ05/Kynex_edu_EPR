<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Controls which roles may log in (Infix "Login Permission").
 * Storage only — enforcement is reported, not wired into auth here.
 */
class LoginPermission extends Model
{
    use HasUlids, TracksCreator;

    protected $fillable = [
        'role',
        'can_login',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'can_login' => 'boolean',
        ];
    }

    /**
     * Whether a given role is permitted to log in. Defaults to true when
     * no explicit row exists (fail-open: only block roles you turn off).
     */
    public static function roleCanLogin(string $role): bool
    {
        $row = static::query()->where('role', $role)->first();

        return $row ? (bool) $row->can_login : true;
    }
}
