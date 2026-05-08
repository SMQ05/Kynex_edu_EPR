<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditRun extends Model
{
    use HasUuids;

    protected $connection = 'central';
    protected $table      = 'audit_runs';

    protected $fillable = [
        'id', 'scope', 'tenant_slug', 'role', 'url', 'status',
        'saas_skipped', 'saas_skip_reason', 'started_at', 'finished_at',
        'total_pages', 'findings_critical', 'findings_high', 'findings_medium',
        'findings_low', 'findings_info', 'last_url_crawled', 'last_role_crawled',
        'json_report_path', 'meta',
    ];

    protected $casts = [
        'saas_skipped' => 'boolean',
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
        'meta'         => 'array',
    ];

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class, 'run_id');
    }
}
