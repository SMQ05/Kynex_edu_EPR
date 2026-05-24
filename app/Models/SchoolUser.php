<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\RequiresApproval;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * SchoolUser — Tenant-scoped user model for the school admin panel.
 *
 * Lives in the tenant database. Represents staff, teachers, and
 * parent/guardian portal users.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $profile_photo_path
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string|null $campus_id
 * @property string|null $active_role
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class SchoolUser extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasUlids, HasRoles, HasFactory, Notifiable, SoftDeletes, RequiresApproval;

    protected $table = 'school_users';

    protected $guard_name = 'school_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'whatsapp',
        'profile_photo_path',
        'is_active',
        'last_login_at',
        'email_verified_at',
        'campus_id',
        'active_role',
        'biometric_device_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function campus(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Campus::class, 'campus_id');
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(\App\Models\Tenant\StaffProfile::class, 'school_user_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(\App\Models\Tenant\Student::class, 'school_user_id');
    }

    // ── Role Helpers ─────────────────────────────────────────────

    /**
     * Switch the user's active role. Must be one of their assigned roles.
     */
    public function switchRole(string $roleName): bool
    {
        if (! $this->hasRole($roleName)) {
            return false;
        }

        $this->update(['active_role' => $roleName]);
        return true;
    }

    /**
     * Get the currently active role name.
     * Falls back to the first assigned role if none is set.
     */
    public function getActiveRoleName(): ?string
    {
        if ($this->active_role && $this->hasRole($this->active_role)) {
            return $this->active_role;
        }

        $firstRole = $this->roles->first();
        return $firstRole?->name;
    }

    /**
     * Returns true if this user holds more than one role (stacked roles).
     * School Admin can freely assign secondary roles to any staff or teacher.
     */
    public function hasStackedRoles(): bool
    {
        return $this->roles->count() > 1;
    }

    /**
     * Returns all role names assigned to this user (superset for stacked roles).
     */
    public function allRoleNames(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Returns the union of all permissions across all assigned roles.
     * Used when a user has multiple stacked roles.
     */
    public function allPermissionsUnion(): \Illuminate\Support\Collection
    {
        // Spatie's getAllPermissions() already returns the union
        return $this->getAllPermissions();
    }

    /**
     * Returns true if this user can mark attendance via manual fallback.
     * Enabled when biometric device is offline or not configured,
     * or when the admin has explicitly granted manual override.
     *
     * Use BiometricDeviceStatus service to check real-time device status.
     */
    public function canMarkAttendanceFallback(): bool
    {
        // Has explicit fallback permission (teachers, admins)
        if ($this->hasPermissionTo('mark_attendance_fallback')) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if user can use biometric device for attendance.
     */
    public function canMarkAttendanceBiometric(): bool
    {
        return $this->hasPermissionTo('mark_attendance_biometric')
            || $this->hasPermissionTo('process_biometric_data');
    }

    // ── Search ───────────────────────────────────────────────────

    /**
     * Full-text search using PostgreSQL tsvector.
     * Falls back to ILIKE for very short terms (< 2 chars).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (strlen($term) < 2) {
            return $query->where('name', 'ilike', "%{$term}%");
        }

        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            [$term]
        )->orderByRaw(
            "ts_rank(search_vector, plainto_tsquery('english', ?)) DESC",
            [$term]
        );
    }

    // ── Filament access ─────────────────────────────────────────

    /**
     * Determines whether this user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->active_role === 'STUDENT') {
            return false;
        }

        if ($this->is_active !== true) {
            return false;
        }

        // Admins always retain access — a misconfigured login/fee rule must
        // never lock out the people who would fix it.
        if ($this->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'])) {
            return true;
        }

        // Login Permission (Phase 7): a role can be switched off. Fail-open
        // on any error (e.g. table not yet migrated) so access is never lost.
        try {
            $activeRole = $this->getActiveRoleName();
            if ($activeRole && ! \App\Models\Tenant\LoginPermission::roleCanLogin($activeRole)) {
                return false;
            }
        } catch (\Throwable) {
            // ignore — fail open
        }

        // Due-Fees login block (Phase 7): optionally bar guardians whose
        // children have overdue fees beyond the grace period. Fail-open.
        try {
            if (! $this->passesDueFeesLoginCheck()) {
                return false;
            }
        } catch (\Throwable) {
            // ignore — fail open
        }

        return true;
    }

    /**
     * Due-fees login gate. Returns true (allowed) unless the feature is on,
     * applies to this guardian, and their children's overdue balance exceeds
     * the configured threshold.
     */
    protected function passesDueFeesLoginCheck(): bool
    {
        $cfg = \App\Models\Tenant\DueFeesLoginSetting::current();
        if (! $cfg->enabled) {
            return true;
        }

        $isGuardian = $this->hasRole('PARENT') || $this->getActiveRoleName() === 'PARENT';
        if (! $isGuardian || ! in_array($cfg->applies_to, ['guardians', 'both'], true)) {
            return true;
        }

        $studentIds = \App\Models\Tenant\Student::query()
            ->whereHas('guardians', function ($q): void {
                $q->where('school_user_id', $this->id)->orWhere('email', $this->email);
            })
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return true;
        }

        $graceDate = now()->subDays((int) $cfg->grace_days);
        $overdue = \App\Models\Tenant\StudentFee::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', \App\Enums\StudentFeeStatus::Pending)
            ->where('due_date', '<', $graceDate)
            ->get();

        $totalDue = (int) $overdue->sum(fn ($f) => max(0, (int) $f->balance_paisas));

        return $totalDue <= (int) ($cfg->min_due_paisas ?? 0);
    }

    /**
     * Determines whether this user can impersonate other users.
     */
    public function canAccessTenancy(): bool
    {
        return true;
    }
}
