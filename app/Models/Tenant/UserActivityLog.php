<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * General user activity / login log (Infix "User Log"). Read-only in the
 * UI; written via App\Support\UserActivity::log().
 *
 * Distinct from PiiAccessLog (PII access only) and any audit logs.
 */
class UserActivityLog extends Model
{
    use HasUlids;

    public const UPDATED_AT = null; // append-only; only created_at

    protected $fillable = [
        'school_user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip',
        'user_agent',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'school_user_id');
    }
}
