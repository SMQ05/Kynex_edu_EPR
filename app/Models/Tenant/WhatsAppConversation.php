<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\WhatsAppConversationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SchoolUser;

/**
 * WhatsAppConversation — One thread per unique contact phone number.
 *
 * A conversation groups all messages (inbound + outbound) with a single
 * parent/guardian. Contact info is de-normalised from StudentGuardian at
 * message-receive time so the inbox works even if the guardian record changes.
 *
 * @property string                       $id
 * @property string                       $contact_phone
 * @property string|null                  $contact_name
 * @property array|null                   $student_ids
 * @property WhatsAppConversationStatus   $status
 * @property \Carbon\Carbon|null          $last_message_at
 * @property int                          $unread_count
 * @property string|null                  $assigned_to
 */
class WhatsAppConversation extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'contact_phone',
        'contact_name',
        'student_ids',
        'status',
        'last_message_at',
        'unread_count',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'status'          => WhatsAppConversationStatus::class,
            'student_ids'     => 'array',
            'last_message_at' => 'datetime',
            'unread_count'    => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')
                    ->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')
                    ->latestOfMany();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'assigned_to');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', WhatsAppConversationStatus::Open);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('unread_count', '>', 0);
    }

    public function scopeForPhone(Builder $query, string $phone): Builder
    {
        return $query->where('contact_phone', $phone);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('contact_name', 'ilike', "%{$term}%")
              ->orWhere('contact_phone', 'ilike', "%{$term}%");
        });
    }

    // ── Helpers ────────────────────────────────────────────────

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
        $this->messages()
             ->whereNull('read_at')
             ->where('direction', 'inbound')
             ->update(['read_at' => now()]);
    }
}
