<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * g. ExamsAndResultsSeeder
 *
 * Seeds:
 *  - grade_rules (Pakistani standard A+/A/B+/B/C/D/F)
 *  - 2 exams: First Term (Feb 2026), Mid Term (April 2026)
 *  - exam_schedules per (exam, class, subject)
 *  - exam_marks per (student, exam_schedule) with realistic distribution
 *  - exam_results aggregated per (student, exam) with grade + class rank
 *
 * Subject coverage uses $this->profile()->classSubjects().
 */
class ExamsAndResultsSeeder extends Seeder
{
    use UsesDemoProfile;

    public string $academicYearId = '';

    /** @var array<string, array{start:string, graded:bool}> */
    protected array $examMeta = [];

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
        public StudentsAndParentsSeeder $studentsAndParents,
    ) {}

    public function run(): void
    {
        $this->academicYearId = $this->classes->academicYearId;
        $this->seedGradeRules();

        $exams = $this->seedExams();
        $schedules = $this->seedSchedules($exams);
        $this->seedMarksAndResults($exams, $schedules);
    }

    protected function seedGradeRules(): void
    {
        DB::table('grade_rules')->delete();
        $rules = $this->profile()->gradeRules();
        foreach ($rules as [$g, $min, $max, $point, $remark]) {
            DB::table('grade_rules')->insert([
                'id' => (string) Str::ulid(),
                'name' => "Grade {$g}",
                'grade' => $g,
                'min_percentage' => $min,
                'max_percentage' => $max,
                'grade_point' => $point,
                'description' => $remark,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ grade_rules seeded (' . count($rules) . ' — ' . $rules[0][0] . ' to ' . end($rules)[0] . ')');
    }

    /**
     * @return array<string, string> [name => exam_id]
     */
    protected function seedExams(): array
    {
        DB::table('exam_marks')->delete();
        DB::table('exam_results')->delete();
        DB::table('exam_schedules')->delete();
        DB::table('exams')->delete();

        $headId = $this->staff->userIdByLabel['principal']
            ?? $this->staff->userIdByLabel['admin'];

        // These are completed, published exams with full results behind them.
        // When the profile defines a previous academic year they belong to it,
        // not to the year that is only a fortnight old — a report card dated
        // inside a term that has barely started reads as broken data.
        $priorYearId = $this->classes->previousAcademicYearId;
        $examYearId = $priorYearId ?: $this->academicYearId;

        $exams = $priorYearId !== ''
            ? [
                'First Term' => ['2025-10-13', '2025-10-23', 50, 'first_term'],
                'Mid Term' => ['2026-02-09', '2026-02-19', 50, 'mid_term'],
            ]
            : [
                'First Term' => ['2026-02-10', '2026-02-20', 50, 'first_term'],
                'Mid Term' => ['2026-04-15', '2026-04-25', 50, 'mid_term'],
            ];
        // A term exam the school has not sat yet. Without it every child's
        // "upcoming exams" panel is empty — the two completed exams belong to
        // last year, and an online assessment only exists for three classes.
        $upcomingStart = Carbon::today()->addWeeks(4)->startOfWeek();
        $ids = [];
        $meta = [];
        foreach ($exams as $name => [$start, $end, $weight, $type]) {
            $id = (string) Str::ulid();
            DB::table('exams')->insert([
                'id' => $id,
                'academic_year_id' => $examYearId,
                'name' => $name,
                'description' => "{$name} examinations",
                'start_date' => $start,
                'end_date' => $end,
                'status' => 'completed',
                'publish_results' => true,
                'created_by' => $headId,
                'weightage_percent' => $weight,
                'weightage_label' => $name,
                'include_in_annual_result' => true,
                'exam_type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ids[$name] = $id;
            $meta[$name] = ['start' => $start, 'graded' => true];
        }

        $upcomingName = 'First Term';
        $upcomingId = (string) Str::ulid();
        DB::table('exams')->insert([
            'id' => $upcomingId,
            'academic_year_id' => $this->academicYearId,
            'name' => $upcomingName,
            'description' => 'First term examinations for the current year.',
            'start_date' => $upcomingStart->toDateString(),
            'end_date' => $upcomingStart->copy()->addDays(9)->toDateString(),
            'status' => 'scheduled',
            'publish_results' => false,
            'created_by' => $headId,
            'weightage_percent' => 50,
            'weightage_label' => $upcomingName,
            'include_in_annual_result' => true,
            'exam_type' => 'first_term',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ids[$upcomingName . ' ' . now()->year] = $upcomingId;
        $meta[$upcomingName . ' ' . now()->year] = [
            'start' => $upcomingStart->toDateString(),
            'graded' => false,
        ];

        $this->examMeta = $meta;
        $this->command?->line('  ✓ exams seeded (' . count($ids) . ', 1 scheduled ahead)');

        return $ids;
    }

    /**
     * @param  array<string, string>  $exams
     * @return array<string, list<array{id:string, exam_id:string, class_id:string, subject_id:string, exam_name:string, class_number:int, subject_name:string}>>
     *         keyed by class_id
     */
    protected function seedSchedules(array $exams): array
    {
        $byClass = [];
        $count = 0;

        foreach ($exams as $examName => $examId) {
            // Follow the exam's own start date. These were hardcoded, so moving
            // an exam left its timetable behind on the old dates.
            $startDate = $this->examMeta[$examName]['start'];

            foreach ($this->profile()->classSubjects() as $classNumber => $subjects) {
                $classId = $this->classes->classIdByNumber[$classNumber];
                $dateCursor = Carbon::parse($startDate);

                foreach ($subjects as $subjectName) {
                    $subjectId = $this->classes->subjectIdByName[$subjectName] ?? null;
                    if (! $subjectId) {
                        continue;
                    }
                    $id = (string) Str::ulid();
                    DB::table('exam_schedules')->insert([
                        'id' => $id,
                        'exam_id' => $examId,
                        'class_id' => $classId,
                        'section_id' => null,
                        'subject_id' => $subjectId,
                        'exam_date' => $dateCursor->toDateString(),
                        'start_time' => '09:00:00',
                        'end_time' => '11:00:00',
                        'room' => "Room {$classNumber}01",
                        'full_marks' => 100,
                        'pass_marks' => 33,
                        'theory_weight' => 100,
                        'practical_weight' => 0,
                        'practical_full_marks' => null,
                        'practical_pass_marks' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $byClass[$classId][] = [
                        'id' => $id,
                        'exam_id' => $examId,
                        'exam_name' => $examName,
                        'class_id' => $classId,
                        'class_number' => $classNumber,
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                    ];
                    $count++;
                    $dateCursor->addDay();
                    if ($dateCursor->isSunday()) {
                        $dateCursor->addDay();
                    }
                }
            }
        }
        $this->command?->line("  ✓ exam_schedules seeded ({$count})");
        return $byClass;
    }

    /**
     * @param  array<string, string>  $exams
     * @param  array<string, list<array>>  $schedulesByClass
     */
    protected function seedMarksAndResults(array $exams, array $schedulesByClass): void
    {
        $teacherIdsBySubject = [];
        foreach (\Database\Seeders\Demo\ClassesSeeder::SUBJECT_TEACHER_LABEL as $subjectName => $label) {
            $teacherIdsBySubject[$subjectName] = $this->staff->userIdByLabel[$label] ?? null;
        }

        $marksRows = 0;
        $resultsRows = 0;
        $marksBuf = [];

        // For ranking, keep per (exam_id, class_id) → list of [student_id, total]
        $studentTotals = [];

        // Group students by class
        $studentsByClass = [];
        foreach ($this->studentsAndParents->studentRows as $s) {
            $studentsByClass[$s['class_id']][] = $s;
        }

        foreach ($schedulesByClass as $classId => $schedules) {
            $studentsHere = $studentsByClass[$classId] ?? [];
            foreach ($studentsHere as $student) {
                // Each student has a "performance band" picked once: 'top'
                // (~10%), 'high' (~30%), 'mid' (~45%), 'low' (~10%), 'fail' (~5%)
                $r = mt_rand(1, 100);
                $band = $r <= 10 ? 'top' : ($r <= 40 ? 'high' : ($r <= 85 ? 'mid' : ($r <= 95 ? 'low' : 'fail')));
                foreach ($schedules as $sch) {
                    // An exam that has not been sat has no marks. Seeding them
                    // would publish results for a paper dated a month from now.
                    if (! ($this->examMeta[$sch['exam_name']]['graded'] ?? true)) {
                        continue;
                    }

                    $marks = $this->generateMark($band);
                    $marksBuf[] = [
                        'id' => (string) Str::ulid(),
                        'exam_schedule_id' => $sch['id'],
                        'student_id' => $student['id'],
                        'marks_obtained' => $marks,
                        'is_absent' => $marks === null,
                        'remarks' => null,
                        'entered_by' => $teacherIdsBySubject[$sch['subject_name']] ?? null,
                        'practical_marks_obtained' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $marksRows++;
                    $studentTotals[$sch['exam_id']][$classId][$student['id']]['total']
                        = ($studentTotals[$sch['exam_id']][$classId][$student['id']]['total'] ?? 0) + ($marks ?? 0);
                    $studentTotals[$sch['exam_id']][$classId][$student['id']]['full']
                        = ($studentTotals[$sch['exam_id']][$classId][$student['id']]['full'] ?? 0) + 100;
                    $studentTotals[$sch['exam_id']][$classId][$student['id']]['fail_count']
                        = ($studentTotals[$sch['exam_id']][$classId][$student['id']]['fail_count'] ?? 0)
                            + ($marks !== null && $marks < 33 ? 1 : 0);

                    if (count($marksBuf) >= 500) {
                        DB::table('exam_marks')->insert($marksBuf);
                        $marksBuf = [];
                    }
                }
            }
        }
        if (! empty($marksBuf)) {
            DB::table('exam_marks')->insert($marksBuf);
        }

        // Build exam_results from accumulated totals.
        foreach ($studentTotals as $examId => $byClass) {
            foreach ($byClass as $classId => $byStudent) {
                // Rank within class for this exam.
                uasort($byStudent, fn($a, $b) => $b['total'] <=> $a['total']);
                $rank = 1;
                foreach ($byStudent as $studentId => $info) {
                    $percentage = $info['full'] > 0 ? round($info['total'] / $info['full'] * 100, 2) : 0;
                    $grade = $this->gradeFor($percentage);
                    $status = $info['fail_count'] > 0 ? 'fail' : 'pass';

                    DB::table('exam_results')->insert([
                        'id' => (string) Str::ulid(),
                        'exam_id' => $examId,
                        'student_id' => $studentId,
                        'class_id' => $classId,
                        'total_marks' => $info['full'],
                        'marks_obtained' => $info['total'],
                        'percentage' => $percentage,
                        'grade' => $grade['grade'],
                        'grade_point' => $grade['point'],
                        'rank' => $rank,
                        'status' => $status,
                        'remarks' => $rank <= 3 ? "top{$rank}" : null,
                        'weighted_percentage' => $percentage * 0.5, // exam weight 50%
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $rank++;
                    $resultsRows++;
                }
            }
        }

        $this->command?->line("  ✓ exam_marks ({$marksRows}), exam_results ({$resultsRows})");
    }

    /**
     * @return float|null Marks 0-100, or null for "absent" (rare).
     */
    protected function generateMark(string $band): ?float
    {
        // 1% absent rate.
        if (mt_rand(1, 100) === 1) {
            return null;
        }
        return match ($band) {
            'top' => (float) mt_rand(90, 100),
            'high' => (float) mt_rand(75, 92),
            'mid' => (float) mt_rand(55, 78),
            'low' => (float) mt_rand(35, 60),
            'fail' => (float) mt_rand(15, 38),
            default => (float) mt_rand(50, 75),
        };
    }

    /**
     * @return array{grade:string, point:float}
     */
    protected function gradeFor(float $percent): array
    {
        return match (true) {
            $percent >= 90 => ['grade' => 'A+', 'point' => 4.0],
            $percent >= 80 => ['grade' => 'A', 'point' => 3.7],
            $percent >= 70 => ['grade' => 'B+', 'point' => 3.3],
            $percent >= 60 => ['grade' => 'B', 'point' => 3.0],
            $percent >= 50 => ['grade' => 'C', 'point' => 2.5],
            $percent >= 40 => ['grade' => 'D', 'point' => 2.0],
            default => ['grade' => 'F', 'point' => 0.0],
        };
    }
}
