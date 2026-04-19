<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class GradeRule extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'grade',
        'min_percentage',
        'max_percentage',
        'grade_point',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_percentage' => 'decimal:2',
            'max_percentage' => 'decimal:2',
            'grade_point'    => 'decimal:1',
            'is_active'      => 'boolean',
        ];
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGradingSystem($query, string $name)
    {
        return $query->where('name', $name);
    }

    // ── Static Helpers ─────────────────────────────────────────────

    /**
     * Given a percentage, return the matching GradeRule for a specific grading system.
     */
    public static function resolveGrade(float $percentage, string $gradingSystem = 'Standard Grading'): ?self
    {
        return static::active()
            ->forGradingSystem($gradingSystem)
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->first();
    }
}
