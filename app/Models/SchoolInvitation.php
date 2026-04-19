<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * SchoolInvitation — Stores one-time tokens for school registration
 * email verification and admin-issued set-password invitations.
 *
 * @property string $id
 * @property string $school_name
 * @property string $contact_name
 * @property string $email
 * @property string|null $phone
 * @property string $type            school_signup | admin_invite
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $consumed_at
 * @property string|null $tenant_id
 * @property array|null $meta
 */
class SchoolInvitation extends Model
{
    use HasUlids;

    protected $table = 'school_invitations';

    protected $fillable = [
        'school_name',
        'contact_name',
        'email',
        'phone',
        'type',
        'token',
        'expires_at',
        'email_verified_at',
        'consumed_at',
        'tenant_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'         => 'datetime',
            'email_verified_at'  => 'datetime',
            'consumed_at'        => 'datetime',
            'meta'               => 'array',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}
