<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A date-scheduled lesson plan: objectives, activities, resources and
 * assessment for delivering a lesson on a given date. Optionally references
 * an existing SyllabusTopic so coverage stays in sync with the syllabus.
 */
class LessonPlan extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'lesson_id',
        'syllabus_topic_id',
        'teacher_id',
        'title',
        'plan_date',
        'week_number',
        'duration_minutes',
        'objectives',
        'activities',
        'teaching_resources',
        'assessment',
        'homework',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'plan_date'        => 'date',
            'week_number'      => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function syllabusTopic(): BelongsTo
    {
        return $this->belongsTo(SyllabusTopic::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'teacher_id');
    }
}
