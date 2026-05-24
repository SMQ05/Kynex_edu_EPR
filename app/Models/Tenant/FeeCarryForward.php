<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Records a fee carry-forward: a student's unpaid balance moved from one
 * academic year into the current one as a new StudentFee invoice.
 */
class FeeCarryForward extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, TracksCreator;

    /** @var list<string> */
    protected array $paisaFields = ['amount_paisas'];

    protected $fillable = [
        'student_id',
        'from_academic_year_id',
        'to_academic_year_id',
        'student_fee_id',
        'amount_paisas',
        'note',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function fromYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class, 'student_fee_id');
    }
}
