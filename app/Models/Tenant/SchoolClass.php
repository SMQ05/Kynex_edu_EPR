<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'numeric_level',
        'sort_order',
        'description',
        'campus_id',
    ];

    protected function casts(): array
    {
        return [
            'numeric_level' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function classRoutines(): HasMany
    {
        return $this->hasMany(ClassRoutine::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function feeMasters(): HasMany
    {
        return $this->hasMany(FeeMaster::class, 'class_id');
    }
}
