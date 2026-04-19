<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\SuccessionStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * RoleSuccession — Tenant-scoped model for staff role handover/succession.
 *
 * When a school user (teacher, bursar, etc.) leaves, a School Admin
 * initiates succession. After Institute Head approval, the role + linked
 * records (class assignments, subjects, workflows) are transferred to the
 * incoming user.
 *
 * Institute Head succession itself is escalated to SaaS Super Admin.
 *
 * @property string                   $id
 * @property string                   $outgoing_user_id
 * @property string|null              $incoming_user_id
 * @property string                   $role_slug
 * @property array|null               $transfer_records
 * @property string                   $approver_level
 * @property SuccessionStatus         $status
 * @property string|null              $requested_by
 * @property string|null              $approved_by_type
 * @property string|null              $approved_by_id
 * @property \Carbon\Carbon|null      $approved_at
 * @property string|null              $requester_notes
 * @property string|null              $approver_notes
 * @property \Carbon\Carbon|null      $completed_at
 * @property \Carbon\Carbon           $created_at
 * @property \Carbon\Carbon           $updated_at
 * @property \Carbon\Carbon|null      $deleted_at
 */
class RoleSuccession extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'role_successions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'           => SuccessionStatus::class,
            'transfer_records' => 'array',
            'approved_at'      => 'datetime',
            'completed_at'     => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function outgoingUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'outgoing_user_id');
    }

    public function incomingUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'incoming_user_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'requested_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SuccessionStatus::Pending);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', SuccessionStatus::Completed);
    }

    public function scopeForRole(Builder $query, string $roleSlug): Builder
    {
        return $query->where('role_slug', $roleSlug);
    }

    public function scopeRequiresInstituteHead(Builder $query): Builder
    {
        return $query->where('approver_level', 'institute_head');
    }

    public function scopeRequiresSaasAdmin(Builder $query): Builder
    {
        return $query->where('approver_level', 'saas_admin');
    }

    // ── State Helpers ────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === SuccessionStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === SuccessionStatus::Completed;
    }

    public function needsSaasAdminApproval(): bool
    {
        return $this->approver_level === 'saas_admin';
    }
}
