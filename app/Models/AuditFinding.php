<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFinding extends Model
{
    use HasUuids;

    protected $connection = 'central';
    protected $table      = 'audit_findings';

    protected $fillable = [
        'id', 'run_id', 'finding_hash', 'tenant_slug', 'role', 'url',
        'finding_type', 'severity', 'title', 'details', 'screenshot_path',
        'http_status', 'detected_at', 'fixed_at', 'fixed_by_commit',
    ];

    protected $casts = [
        'details'     => 'array',
        'detected_at' => 'datetime',
        'fixed_at'    => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class, 'run_id');
    }
}
