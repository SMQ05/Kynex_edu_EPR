<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InAppNotification — Tenant-scoped notification records for the UI bell.
 *
 * Push delivery tracking columns (push_sent_at, push_delivered_at, fallback_*)
 * were added in Phase 11. These are nullable and safe for mass-assignment since
 * only the NotificationService and CheckPushDelivery job write to them.
 *
 * NOTE (Risk 7): The expanded $fillable array allows these new timestamp columns.
 * Existing code that calls ::create() with only the original columns is unaffected
 * because the new columns are all nullable with no default constraints.
 */
class InAppNotification extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'action_url',
        'read_at',
        'push_sent_at',
        'push_delivered_at',
        'fallback_sent_at',
        'fallback_channel',
    ];

    protected function casts(): array
    {
        return [
            'read_at'           => 'datetime',
            'push_sent_at'      => 'datetime',
            'push_delivered_at' => 'datetime',
            'fallback_sent_at'  => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
