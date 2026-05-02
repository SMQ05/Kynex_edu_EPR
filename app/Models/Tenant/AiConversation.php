<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversation extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'school_user_id',
        'role_when_created',
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'school_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at');
    }
}
