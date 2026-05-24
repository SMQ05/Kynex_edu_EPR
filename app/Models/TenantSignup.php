<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SignupStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * TenantSignup — CRM-lite record for prospective school signups.
 *
 * Tracks schools interested in the platform through the signup pipeline:
 * new → contacted → invited → onboarded (or rejected).
 *
 * @property string $id              ULID primary key
 * @property string $school_name
 * @property string $contact_name
 * @property string $email
 * @property string $phone
 * @property string|null $whatsapp_number
 * @property string|null $city
 * @property string $country
 * @property string|null $message
 * @property SignupStatus $status
 * @property \Carbon\Carbon|null $invited_at
 * @property \Carbon\Carbon|null $onboarded_at
 * @property string|null $internal_notes
 */
class TenantSignup extends Model
{
    use HasUlids;

    // ── Table & Key ────────────────────────────────────────────
    protected $table = 'tenant_signups';

    /**
     * Always resolve against the central DB — tenant_signups is a
     * central-only table. Prevents tenant-DB lookups when tenancy is active.
     */
    protected $connection = 'central';

    // ── Mass Assignment ────────────────────────────────────────
    protected $fillable = [
        'school_name',
        'contact_name',
        'email',
        'phone',
        'whatsapp_number',
        'city',
        'country',
        'message',
        'status',
        'invited_at',
        'onboarded_at',
        'internal_notes',
    ];

    // ── Casts ──────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'status'       => SignupStatus::class,
            'invited_at'   => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }
}
