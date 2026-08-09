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
 * Teacher-uploaded study material (file or external link) targeted at a
 * class/subject for students. `category` discriminates study material vs.
 * general "Other Downloads".
 */
class StudyMaterial extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const CATEGORIES = [
        'study_material'  => 'Study Material',
        'other_download'  => 'Other Download',
        'assignment_help' => 'Assignment Help',
        'syllabus_doc'    => 'Syllabus Document',
    ];

    protected $fillable = [
        'title',
        'description',
        'category',
        'class_id',
        'section_id',
        'subject_id',
        'academic_year_id',
        'teacher_id',
        'source_type',
        'file_path',
        'external_url',
        'file_type',
        'available_from',
        'is_published',
        'download_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'available_from' => 'date',
            'is_published'   => 'boolean',
            'download_count' => 'integer',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'teacher_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /** Revision cards for this lecture, in teaching order. */
    public function flashcards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LectureFlashcard::class, 'study_material_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Practice questions for this lecture.
     *
     * These live in exam_questions rather than a parallel table, so a teacher
     * can promote one into a real exam without retyping it.
     */
    public function practiceQuestions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'study_material_id')
            ->where('is_active', true)
            ->orderBy('created_at');
    }

    public function quizAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LectureQuizAttempt::class, 'study_material_id');
    }
}
