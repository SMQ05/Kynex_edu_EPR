<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Support;

/**
 * Shared total/max/percentage computation from the criteria_scores repeater,
 * used by both the Create and Edit pages so saved totals always match the
 * entered criteria.
 */
trait ComputesEvaluationScore
{
    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function withComputedScore(array $data): array
    {
        $total = 0.0;
        $max = 0.0;

        foreach ((array) ($data['criteria_scores'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $total += (float) ($row['score'] ?? 0);
            $max   += (float) ($row['max'] ?? 0);
        }

        $data['total_score'] = round($total, 2);
        $data['max_score']   = round($max, 2);
        $data['percentage']  = $max > 0 ? round($total / $max * 100, 2) : null;

        return $data;
    }
}
