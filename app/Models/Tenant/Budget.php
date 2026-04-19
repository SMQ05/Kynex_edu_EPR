<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    /** @var list<string> */
    protected array $paisaFields = ['budgeted_amount_paisas', 'spent_amount_paisas'];

    protected $fillable = [
        'academic_year_id',
        'category_id',
        'title',
        'budgeted_amount_paisas',
        'spent_amount_paisas',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'budgeted_amount_paisas' => 'integer',
            'spent_amount_paisas' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function getRemainingPaisasAttribute(): int
    {
        return $this->budgeted_amount_paisas - $this->spent_amount_paisas;
    }

    public function getUtilizationPercentAttribute(): float
    {
        if ($this->budgeted_amount_paisas === 0) {
            return 0.0;
        }

        return round(($this->spent_amount_paisas / $this->budgeted_amount_paisas) * 100, 2);
    }
}
