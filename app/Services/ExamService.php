<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExamResultStatus;
use App\Models\Tenant\DailyActivityLog;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\GradeRule;
use App\Models\Tenant\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    // ── Calculate & store results for an entire exam ────────────────

    /**
     * Calculate aggregated results for every student who has marks in this exam.
     * Protected by advisory lock to prevent duplicate calculation from concurrent workers.
     * Optionally filter by class_id.
     */
    public function calculateResults(string $examId, ?string $classId = null, string $gradingSystem = 'Standard Grading'): int
    {
        $lockKey = crc32("exam-results:{$examId}");

        $acquired = DB::selectOne(
            'SELECT pg_try_advisory_lock(?) AS acquired',
            [$lockKey]
        )->acquired;

        if (! $acquired) {
            throw new \RuntimeException(
                "Result calculation for this exam is already running. Please wait."
            );
        }

        try {
            return $this->doCalculateResults($examId, $classId, $gradingSystem);
        } finally {
            DB::statement('SELECT pg_advisory_unlock(?)', [$lockKey]);
        }
    }

    /**
     * Actual result calculation logic (called inside advisory lock).
     */
    private function doCalculateResults(string $examId, ?string $classId, string $gradingSystem): int
    {
        $exam = Exam::findOrFail($examId);

        $scheduleQuery = ExamSchedule::where('exam_id', $examId);
        if ($classId) {
            $scheduleQuery->where('class_id', $classId);
        }
        $schedules = $scheduleQuery->get();

        if ($schedules->isEmpty()) {
            return 0;
        }

        // Group schedules by class
        $schedulesByClass = $schedules->groupBy('class_id');
        $totalResults = 0;

        DB::transaction(function () use ($exam, $schedulesByClass, $gradingSystem, &$totalResults) {
            foreach ($schedulesByClass as $cId => $classSchedules) {
                $scheduleIds = $classSchedules->pluck('id');
                $fullMarksMap = $classSchedules->pluck('full_marks', 'id');
                $passMarksMap = $classSchedules->pluck('pass_marks', 'id');

                // Get all students who have marks in any of these schedules
                $studentIds = ExamMark::whereIn('exam_schedule_id', $scheduleIds)
                    ->distinct()
                    ->pluck('student_id');

                foreach ($studentIds as $studentId) {
                    $marks = ExamMark::where('student_id', $studentId)
                        ->whereIn('exam_schedule_id', $scheduleIds)
                        ->get();

                    $totalFull     = 0;
                    $totalObtained = 0;
                    $allAbsent     = true;
                    $anyFail       = false;

                    foreach ($marks as $mark) {
                        $schedFull = $fullMarksMap[$mark->exam_schedule_id] ?? 0;
                        $schedPass = $passMarksMap[$mark->exam_schedule_id] ?? 0;
                        $totalFull += $schedFull;

                        if ($mark->is_absent) {
                            // Absent in a subject counts as 0
                            $anyFail = true;
                        } else {
                            $allAbsent = false;
                            $totalObtained += $mark->marks_obtained ?? 0;

                            if (($mark->marks_obtained ?? 0) < $schedPass) {
                                $anyFail = true;
                            }
                        }
                    }

                    $percentage = $totalFull > 0
                        ? round(($totalObtained / $totalFull) * 100, 2)
                        : 0;

                    // Determine status
                    if ($allAbsent) {
                        $status = ExamResultStatus::Absent;
                    } elseif ($anyFail) {
                        $status = ExamResultStatus::Fail;
                    } else {
                        $status = ExamResultStatus::Pass;
                    }

                    // Resolve grade
                    $gradeRule  = GradeRule::resolveGrade($percentage, $gradingSystem);
                    $grade      = $gradeRule?->grade;
                    $gradePoint = $gradeRule?->grade_point;

                    // ── PART 5f: Weighted percentage calculation ──────────────
                    // Apply weightage_percent from the exam (default 100 if not set)
                    // and factor in DailyActivityLog average score (max 30 pts → 30%)
                    $weightagePercent    = (int) ($exam->weightage_percent ?? 100);
                    $includeInAnnual     = (bool) ($exam->include_in_annual_result ?? true);

                    // Exam contributes weightagePercent% of its raw percentage
                    $examContribution = $percentage * ($weightagePercent / 100);

                    // Activity score: average total_score (0-30) across all logs for this student in exam's class
                    // Scaled to 0-30%, then applied proportionally to remaining weight
                    $activityWeight = 100 - $weightagePercent;  // remaining weight for activity
                    $activityAvg    = 0;

                    if ($activityWeight > 0) {
                        $activityAvg = DailyActivityLog::where('student_id', $studentId)
                            ->where('class_id', $cId)
                            ->avg('total_score') ?? 0;
                        // total_score max = 30; scale to 0-100 first
                        $activityPct = round(($activityAvg / 30) * 100, 2);
                    } else {
                        $activityPct = 0;
                    }

                    $weightedPercentage = $includeInAnnual
                        ? round($examContribution + ($activityPct * ($activityWeight / 100)), 2)
                        : $percentage; // if not included in annual, store raw percentage

                    ExamResult::updateOrCreate(
                        [
                            'exam_id'    => $exam->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'class_id'          => $cId,
                            'total_marks'       => $totalFull,
                            'marks_obtained'    => $totalObtained,
                            'percentage'        => $percentage,
                            'weighted_percentage'=> $weightedPercentage,
                            'grade'             => $grade,
                            'grade_point'       => $gradePoint,
                            'status'            => $status,
                        ]
                    );

                    $totalResults++;
                }

                // Calculate ranks within this class
                $this->assignRanks($exam->id, $cId);
            }
        });

        return $totalResults;
    }

    // ── Assign Ranks ───────────────────────────────────────────────

    /**
     * Assign rank based on marks_obtained descending within a class for an exam.
     * Uses PostgreSQL RANK() OVER — ties get same rank, next rank skips (1,2,2,4).
     */
    public function assignRanks(string $examId, string $classId): void
    {
        // Rank passing students using SQL window function
        DB::statement("
            UPDATE exam_results er
            SET rank = ranked.new_rank
            FROM (
                SELECT
                    id,
                    RANK() OVER (
                        ORDER BY marks_obtained DESC NULLS LAST
                    ) as new_rank
                FROM exam_results
                WHERE exam_id = ?
                  AND class_id = ?
                  AND status = ?
            ) ranked
            WHERE er.id = ranked.id
              AND er.exam_id = ?
              AND er.class_id = ?
        ", [$examId, $classId, ExamResultStatus::Pass->value, $examId, $classId]);

        // Set rank to null for non-passing students
        ExamResult::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('status', '!=', ExamResultStatus::Pass)
            ->update(['rank' => null]);
    }

    // ── Merit List ─────────────────────────────────────────────────

    /**
     * Get merit list (top N students) for an exam in a class.
     * Uses PostgreSQL ROW_NUMBER() OVER for deterministic positioning.
     */
    public function getMeritList(string $examId, string $classId, int $limit = 10): Collection
    {
        return collect(DB::select("
            SELECT
                er.*,
                s.first_name,
                s.last_name,
                s.admission_number,
                ROW_NUMBER() OVER (
                    ORDER BY er.marks_obtained DESC NULLS LAST
                ) as position
            FROM exam_results er
            JOIN students s ON s.id = er.student_id
            WHERE er.exam_id = ?
              AND er.class_id = ?
              AND er.status = ?
            ORDER BY position
            LIMIT ?
        ", [$examId, $classId, ExamResultStatus::Pass->value, $limit]));
    }

    // ── Student Report Card ────────────────────────────────────────

    /**
     * Get complete report card data for a student in an exam.
     */
    public function getStudentReportCard(string $examId, string $studentId): array
    {
        $student = Student::findOrFail($studentId);
        $result  = ExamResult::where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->first();

        $schedules = ExamSchedule::where('exam_id', $examId)
            ->where('class_id', $student->class_id)
            ->with('subject')
            ->get();

        $subjectWise = [];
        foreach ($schedules as $schedule) {
            $mark = ExamMark::where('exam_schedule_id', $schedule->id)
                ->where('student_id', $studentId)
                ->first();

            $subjectWise[] = [
                'subject'        => $schedule->subject?->name ?? 'N/A',
                'full_marks'     => $schedule->full_marks,
                'pass_marks'     => $schedule->pass_marks,
                'marks_obtained' => $mark?->marks_obtained,
                'is_absent'      => $mark?->is_absent ?? false,
                'is_pass'        => $mark ? ($mark->is_absent ? false : ($mark->marks_obtained >= $schedule->pass_marks)) : false,
                'remarks'        => $mark?->remarks,
            ];
        }

        return [
            'student'      => $student,
            'result'       => $result,
            'subjects'     => $subjectWise,
            'total_marks'  => $result?->total_marks ?? 0,
            'obtained'     => $result?->marks_obtained ?? 0,
            'percentage'   => $result?->percentage ?? 0,
            'grade'        => $result?->grade,
            'grade_point'  => $result?->grade_point,
            'rank'         => $result?->rank,
            'status'       => $result?->status,
        ];
    }

    // ── Class Summary ──────────────────────────────────────────────

    /**
     * Get class-level summary statistics for an exam.
     */
    public function getClassResultSummary(string $examId, string $classId): array
    {
        $results = ExamResult::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->get();

        if ($results->isEmpty()) {
            return [
                'total_students' => 0,
                'pass_count'     => 0,
                'fail_count'     => 0,
                'absent_count'   => 0,
                'pass_rate'      => 0,
                'highest'        => 0,
                'lowest'         => 0,
                'average'        => 0,
            ];
        }

        $passed  = $results->where('status', ExamResultStatus::Pass);
        $failed  = $results->where('status', ExamResultStatus::Fail);
        $absent  = $results->where('status', ExamResultStatus::Absent);
        $scoring = $results->whereNotIn('status', [ExamResultStatus::Absent]);

        return [
            'total_students' => $results->count(),
            'pass_count'     => $passed->count(),
            'fail_count'     => $failed->count(),
            'absent_count'   => $absent->count(),
            'pass_rate'      => $results->count() > 0
                ? round(($passed->count() / $results->count()) * 100, 1)
                : 0,
            'highest'        => $scoring->max('percentage') ?? 0,
            'lowest'         => $scoring->min('percentage') ?? 0,
            'average'        => round($scoring->avg('percentage') ?? 0, 2),
        ];
    }

    // ── Bulk Marks Entry Helper ────────────────────────────────────

    /**
     * Save marks for multiple students in a single exam schedule.
     *
     * @param  array  $marksData  Array of ['student_id' => ..., 'marks_obtained' => ..., 'is_absent' => ...]
     */
    public function saveMarks(string $examScheduleId, array $marksData, string $enteredBy): int
    {
        $count = 0;

        DB::transaction(function () use ($examScheduleId, $marksData, $enteredBy, &$count) {
            foreach ($marksData as $entry) {
                ExamMark::updateOrCreate(
                    [
                        'exam_schedule_id' => $examScheduleId,
                        'student_id'       => $entry['student_id'],
                    ],
                    [
                        'marks_obtained' => $entry['is_absent'] ? null : ($entry['marks_obtained'] ?? null),
                        'is_absent'      => $entry['is_absent'] ?? false,
                        'remarks'        => $entry['remarks'] ?? null,
                        'entered_by'     => $enteredBy,
                    ]
                );
                $count++;
            }
        });

        return $count;
    }
}
