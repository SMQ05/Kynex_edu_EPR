<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\AssignmentType;
use App\Models\Concerns\RequiresApproval;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HomeworkAssignment — Represents a homework/assignment given to a class section.
 *
 * @property string      $id
 * @property string      $class_id
 * @property string      $section_id
 * @property string      $subject_id
 * @property string      $teacher_id
 * @property string      $title
 * @property string      $description
 * @property \Carbon\Carbon $due_date
 * @property string|null $attachment_path
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class HomeworkAssignment extends Model
{
    use HasUlids, SoftDeletes, RequiresApproval;

    protected $table = 'homework_assignments';

    protected $fillable = [
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'type',
        'total_marks',
        'title',
        'description',
        'due_date',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'due_date'    => 'date',
            'type'        => AssignmentType::class,
            'total_marks' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /** The teacher who assigned the homework. */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SchoolUser::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'homework_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('due_date', '>=', now()->toDateString());
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date->isPast();
    }

    public function getPendingSubmissionsCountAttribute(): int
    {
        return $this->submissions()->whereNull('grade')->count();
    }
}
