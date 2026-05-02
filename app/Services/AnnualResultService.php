<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AnnualResult;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\GradeRule;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AnnualResultService — aggregates per-student exam results, homework
 * scores, and class-assignment scores into a single weighted annual
 * result row per (academic_year, student) pair.
 *
 * Component weights are taken from `academic_years` (defaults 80/10/10).
 * Each component is normalised to a 0–100 percentage; the final
 * percentage is the weighted sum of the three.
 *
 * Letter grade is resolved via GradeRule (existing infrastructure).
 */
class AnnualResultService
{
    /**
     * Compute and persist annual results for every student in the year.
     * Optionally narrow to a specific class.
     */
    public function computeForAcademicYear(string $academicYearId, ?string $classId = null, string $gradingSystem = 'Standard Grading'): int
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::findOrFail($academicYearId);

        $examWeight     = (int) ($year->exam_weight_percent ?? 80);
        $hwWeight       = (int) ($year->homework_weight_percent ?? 10);
        $assignmentWt   = (int) ($year->class_assignment_weight_percent ?? 10);
        $sum            = $examWeight + $hwWeight + $assignmentWt;
        if ($sum !== 100) {
            // Renormalise defensively if weights drift; surface to caller via exception.
            throw new \RuntimeException("Academic year weights must sum to 100 (got {$sum}). Edit the academic year and try again.");
        }

        $students = Student::query()
            ->where('academic_year_id', $academicYearId)
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->get();

        $count = 0;

        DB::transaction(function () use ($students, $year, $examWeight, $hwWeight, $assignmentWt, $gradingSystem, &$count, $classId) {
            foreach ($students as $student) {
                $examPct       = $this->computeExamScore($student->id, $year->id);
                $homeworkPct   = $this->computeAssignmentScore($student->id, $year->id, 'homework');
                $assignmentPct = $this->computeAssignmentScore($student->id, $year->id, 'class_assignment');

                $finalPct = round(
                    ($examPct * $examWeight + $homeworkPct * $hwWeight + $assignmentPct * $assignmentWt) / 100,
                    2,
                );

                $rule = GradeRule::resolveGrade($finalPct, $gradingSystem);

                AnnualResult::updateOrCreate(
                    [
                        'academic_year_id' => $year->id,
                        'student_id'       => $student->id,
                    ],
                    [
                        'class_id'                         => $student->class_id,
                        'section_id'                       => $student->section_id,
                        'exam_score'                       => $examPct,
                        'homework_score'                   => $homeworkPct,
                        'class_assignment_score'           => $assignmentPct,
                        'exam_weight_percent'              => $examWeight,
                        'homework_weight_percent'          => $hwWeight,
                        'class_assignment_weight_percent'  => $assignmentWt,
                        'final_percentage'                 => $finalPct,
                        'grade'                            => $rule?->grade,
                        'grade_point'                      => $rule?->grade_point,
                        'status'                           => $finalPct >= 33 ? 'pass' : 'fail',
                        'generated_at'                     => now(),
                    ],
                );
                $count++;
            }

            // Ranking within (class, section) — descending by final_percentage.
            $this->rankResults($year->id, $classId);
        });

        return $count;
    }

    /**
     * Sum exam_results.weighted_percentage across the academic year for a
     * student. Falls back to plain percentage average when weighted is null.
     */
    private function computeExamScore(string $studentId, string $academicYearId): float
    {
        $rows = ExamResult::query()
            ->whereHas('exam', fn ($q) => $q->where('academic_year_id', $academicYearId)
                ->where('include_in_annual_result', true))
            ->where('student_id', $studentId)
            ->get(['percentage', 'weighted_percentage']);

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row->weighted_percentage ?? $row->percentage ?? 0);
        }

        return round(min($total, 100.0), 2);
    }

    /**
     * Average percentage across all graded submissions of the given
     * assignment type within the academic year.
     */
    private function computeAssignmentScore(string $studentId, string $academicYearId, string $type): float
    {
        $submissions = HomeworkSubmission::query()
            ->whereNotNull('marks_obtained')
            ->where('student_id', $studentId)
            ->whereHas('homework', function ($q) use ($academicYearId, $type) {
                $q->where('type', $type)
                    ->whereHas('schoolClass', function ($c) use ($academicYearId) {
                        // Filter via student's academic year alignment — class doesn't directly
                        // own academic_year_id, so we constrain via the class_subjects join.
                        $c->whereHas('classSubjects', fn ($cs) => $cs->where('academic_year_id', $academicYearId));
                    });
            })
            ->get(['marks_obtained', 'total_marks']);

        if ($submissions->isEmpty()) {
            return 0.0;
        }

        $sum = 0.0;
        $n   = 0;
        foreach ($submissions as $s) {
            $total = (int) ($s->total_marks ?? 0);
            if ($total <= 0) {
                continue;
            }
            $sum += ((float) $s->marks_obtained * 100.0) / $total;
            $n++;
        }

        return $n > 0 ? round($sum / $n, 2) : 0.0;
    }

    /**
     * Assign rank (1-based, dense) within each (class, section).
     */
    private function rankResults(string $academicYearId, ?string $classId): void
    {
        $query = AnnualResult::query()->where('academic_year_id', $academicYearId);
        if ($classId) {
            $query->where('class_id', $classId);
        }

        $byClass = $query->get()->groupBy(function ($r) {
            return $r->class_id . '|' . ($r->section_id ?? 'none');
        });

        foreach ($byClass as $rows) {
            $sorted = $rows->sortByDesc('final_percentage')->values();
            foreach ($sorted as $i => $row) {
                $row->rank = $i + 1;
                $row->save();
            }
        }
    }
}
