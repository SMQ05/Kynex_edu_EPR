<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\SubjectType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'subject_type',
        'credit_hours',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'subject_type' => SubjectType::class,
            'credit_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'subject_id');
    }

    public function classRoutines(): HasMany
    {
        return $this->hasMany(ClassRoutine::class, 'subject_id');
    }

    public function onlineClasses(): HasMany
    {
        return $this->hasMany(OnlineClass::class, 'subject_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
