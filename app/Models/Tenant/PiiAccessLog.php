<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * PiiAccessLog — Read-only model for the PII audit trail table.
 *
 * Populated by PostgreSQL triggers (log_pii_access function).
 * Records every INSERT/UPDATE/DELETE on sensitive tables.
 */
class PiiAccessLog extends Model
{
    protected $table = 'pii_access_log';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'integer';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'old_data'    => 'array',
            'new_data'    => 'array',
            'accessed_at' => 'datetime',
        ];
    }
}
