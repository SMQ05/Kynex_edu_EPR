<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A user-to-user chat thread (distinct from the AI assistant's
 * AiConversation and from WhatsAppConversation).
 */
class Conversation extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'title',
        'type',
        'last_message_id',
        'last_message_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolUser::class,
            'conversation_participants',
            'conversation_id',
            'school_user_id'
        )->withPivot(['role', 'last_read_at', 'is_muted'])->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    /** Conversations that include the given school user. */
    public function scopeForUser($query, string $schoolUserId)
    {
        return $query->whereHas('participants', fn ($q) => $q->where('school_user_id', $schoolUserId));
    }
}
