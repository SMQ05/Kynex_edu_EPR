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
 * Teacher Evaluation: scored review of a staff member against criteria for a
 * period. AI sentiment + summary of the qualitative feedback.
 */
class TeacherEvaluation extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const STATUSES = [
        'draft'     => 'Draft',
        'submitted' => 'Submitted',
        'approved'  => 'Approved',
    ];

    protected $fillable = [
        'staff_id',
        'evaluator_id',
        'academic_year_id',
        'period',
        'evaluation_date',
        'criteria_scores',
        'total_score',
        'max_score',
        'percentage',
        'strengths',
        'improvements',
        'comments',
        'sentiment',
        'ai_summary',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'criteria_scores' => 'array',
            'evaluation_date' => 'date',
            'total_score'     => 'decimal:2',
            'max_score'       => 'decimal:2',
            'percentage'      => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'evaluator_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Recompute total/max/percentage from the criteria_scores JSON.
     */
    public function recalculateScores(): void
    {
        $total = 0.0;
        $max = 0.0;

        foreach ((array) $this->criteria_scores as $row) {
            if (! is_array($row)) {
                continue;
            }
            $total += (float) ($row['score'] ?? 0);
            $max   += (float) ($row['max'] ?? 0);
        }

        $this->total_score = round($total, 2);
        $this->max_score   = round($max, 2);
        $this->percentage  = $max > 0 ? round($total / $max * 100, 2) : null;
    }
}
