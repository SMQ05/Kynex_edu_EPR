<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * ApprovalRequest — Central DB model for cross-tenant approval workflows.
 *
 * Lives in CENTRAL DB so SaaS admin can see all pending approvals
 * across all schools. Individual school approvals (owner approving
 * admin actions) are routed through here too, with tenant_id set.
 *
 * @property string                   $id
 * @property string|null              $tenant_id
 * @property string                   $requested_by_type
 * @property string                   $requested_by_id
 * @property string                   $action_type
 * @property string                   $approver_level  'institute_head' | 'saas_admin'
 * @property string                   $subject_type
 * @property string                   $subject_id
 * @property array|null               $payload
 * @property string                   $status
 * @property string|null              $reviewed_by_type
 * @property string|null              $reviewed_by_id
 * @property \Carbon\Carbon|null      $reviewed_at
 * @property string|null              $review_notes
 * @property \Carbon\Carbon|null      $expires_at
 * @property \Carbon\Carbon           $created_at
 * @property \Carbon\Carbon           $updated_at
 * @property \Carbon\Carbon|null      $deleted_at
 */
class ApprovalRequest extends Model
{
    use HasUlids, SoftDeletes;

    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'reviewed_at' => 'datetime',
            'expires_at'  => 'datetime',
            'status'      => \App\Enums\ApprovalStatus::class,
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    /** Who requested the action (SaasAdmin or SchoolUser). */
    public function requestedBy(): MorphTo
    {
        return $this->morphTo('requested_by');
    }

    /** The model being acted upon. */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }

    /** Who reviewed the request. */
    public function reviewedBy(): MorphTo
    {
        return $this->morphTo('reviewed_by');
    }

    /** The tenant this request belongs to. */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    /** Filter to only pending requests. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /** Filter to a specific tenant. */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /** Filter to requests expiring within the next 24 hours. */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('status', 'pending')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now()->addDay());
    }

    /** Filter to requests that require Institute Head approval. */
    public function scopeForInstituteHead(Builder $query): Builder
    {
        return $query->where('approver_level', 'institute_head');
    }

    /** Filter to requests escalated to SaaS Super Admin. */
    public function scopeForSaasAdmin(Builder $query): Builder
    {
        return $query->where('approver_level', 'saas_admin');
    }
}
