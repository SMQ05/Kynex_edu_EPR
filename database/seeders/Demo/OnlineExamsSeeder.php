<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Online exams with AI grading — the assessment half of the AI story.
 *
 * This is distinct from AI-marked homework. Here the student sits the paper in
 * the system, auto-gradable items are scored immediately, and open-response
 * items are routed to the AI grader, which is what
 * ExamQuestion::needsAiGrading() decides.
 *
 * Three exams are seeded, one per state, so a demo can show the whole arc
 * without waiting on anything:
 *
 *   graded   — window closed, attempts sat and AI-graded. Open this to show a
 *              customer the marks and the AI's written feedback.
 *   open     — window open now, so an exam can genuinely be sat during the
 *              demo and graded live.
 *   upcoming — window in the future, so the schedule is not empty.
 *
 * Every exam has ai_grade_enabled = true and a question mix that includes
 * ungraded open-response items. A bank of pure multiple choice would never
 * exercise the AI path at all, which would make the feature invisible.
 */
class OnlineExamsSeeder extends Seeder
{
    use UsesDemoProfile;

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
        public StudentsAndParentsSeeder $studentsAndParents,
    ) {}

    public function run(): void
    {
        DB::table('online_exam_answers')->delete();
        DB::table('online_exam_attempts')->delete();
        DB::table('online_exam_questions')->delete();
        DB::table('online_exams')->delete();
        DB::table('exam_questions')->delete();
        DB::table('question_groups')->delete();

        $bank = $this->profile()->onlineExams();
        if ($bank === []) {
            $this->command?->line('  ✓ online exams skipped (profile has no exam bank)');

            return;
        }

        $examCount = 0;
        $questionCount = 0;
        $attemptCount = 0;
        $answerCount = 0;
        $aiGradedAnswers = 0;

        foreach ($bank as $spec) {
            $classId = $this->classes->classIdByNumber[$spec['level']] ?? null;
            $subjectId = $this->classes->subjectIdByName[$spec['subject']] ?? null;

            if ($classId === null || $subjectId === null) {
                continue;
            }

            $teacherId = $this->teacherFor($spec['subject']);

            // ── Question bank ──────────────────────────────────────
            $groupId = (string) Str::ulid();
            DB::table('question_groups')->insert([
                'id' => $groupId,
                'name' => $spec['name'] . ' — Question Bank',
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'description' => 'Questions used by the "' . $spec['name'] . '" online assessment.',
                'is_active' => true,
                'created_by' => $teacherId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $questionIds = [];
            foreach ($spec['questions'] as $sort => [$type, $text, $options, $correct, $marks, $explanation]) {
                $qid = (string) Str::ulid();
                DB::table('exam_questions')->insert([
                    'id' => $qid,
                    'question_group_id' => $groupId,
                    'subject_id' => $subjectId,
                    'type' => $type,
                    'difficulty' => $marks >= 8 ? 'hard' : ($marks >= 4 ? 'medium' : 'easy'),
                    'question_text' => $text,
                    'options' => $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                    // Leaving correct_answer null is what routes an item to the
                    // AI grader — see ExamQuestion::needsAiGrading().
                    'correct_answer' => $correct,
                    'explanation' => $explanation,
                    'marks' => $marks,
                    'is_active' => true,
                    'created_by' => $teacherId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $questionIds[] = ['id' => $qid, 'type' => $type, 'correct' => $correct,
                                  'options' => $options, 'marks' => $marks, 'sort' => $sort];
                $questionCount++;
            }

            // ── The exam ───────────────────────────────────────────
            [$opensAt, $closesAt, $status] = match ($spec['state']) {
                'graded' => [$this->withinTerm(Carbon::now()->subDays(12))->setTime(9, 0), $this->withinTerm(Carbon::now()->subDays(11))->setTime(17, 0), 'closed'],
                'open' => [Carbon::now()->subHours(2), Carbon::now()->addDays(5)->setTime(23, 59), 'published'],
                default => [Carbon::now()->addDays(9)->setTime(9, 0), Carbon::now()->addDays(10)->setTime(17, 0), 'published'],
            };

            $totalMarks = array_sum(array_column($questionIds, 'marks'));
            $examId = (string) Str::ulid();

            DB::table('online_exams')->insert([
                'id' => $examId,
                'academic_year_id' => $this->classes->academicYearId,
                'class_id' => $classId,
                'section_id' => null,
                'subject_id' => $subjectId,
                'exam_id' => null,
                'name' => $spec['name'],
                'description' => 'Online assessment for ' . $spec['subject'] . '. '
                    . 'Multiple-choice items are scored automatically; written answers are graded with AI assistance and reviewed by the subject teacher.',
                'instructions' => "You have {$spec['duration']} minutes once you begin.\n"
                    . "Answer every question. Written answers should be in full sentences.\n"
                    . "Your score for multiple-choice questions appears immediately; written answers are graded shortly after you submit.",
                'duration_minutes' => $spec['duration'],
                'total_marks' => $totalMarks,
                'passing_marks' => (int) ceil($totalMarks * 0.6),
                'shuffle_questions' => true,
                'show_score_to_student' => true,
                'ai_grade_enabled' => true,
                'status' => $status,
                'window_opens_at' => $opensAt,
                'window_closes_at' => $closesAt,
                'created_by' => $teacherId,
                'created_at' => $this->withinTerm($opensAt->copy()->subDays(6)),
                'updated_at' => now(),
            ]);
            $examCount++;

            foreach ($questionIds as $q) {
                DB::table('online_exam_questions')->insert([
                    'id' => (string) Str::ulid(),
                    'online_exam_id' => $examId,
                    'exam_question_id' => $q['id'],
                    'marks' => $q['marks'],
                    'sort_order' => $q['sort'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Only the closed exam has attempts. The open one is left
            // deliberately unsat so it can be taken live in the demo.
            if ($spec['state'] !== 'graded') {
                continue;
            }

            $students = array_values(array_filter(
                $this->studentsAndParents->studentRows,
                fn (array $s) => $s['class_id'] === $classId,
            ));

            foreach ($students as $idx => $student) {
                // A strong, a middling and a weak performance, cycled, so the
                // AI feedback on show is not uniformly glowing.
                $band = ['strong', 'middling', 'weak'][$idx % 3];

                $startedAt = $opensAt->copy()->addMinutes(mt_rand(5, 180));
                $submittedAt = $startedAt->copy()->addMinutes(mt_rand((int) ($spec['duration'] * 0.5), $spec['duration']));

                $attemptId = (string) Str::ulid();
                $obtained = 0;

                // Insert the attempt BEFORE its answers: online_exam_answers
                // has a foreign key onto online_exam_attempts, so writing the
                // answers first fails. Totals are filled in afterwards, once
                // the per-answer marks are known.
                DB::table('online_exam_attempts')->insert([
                    'id' => $attemptId,
                    'online_exam_id' => $examId,
                    'student_id' => $student['id'],
                    'started_at' => $startedAt,
                    'submitted_at' => $submittedAt,
                    'expires_at' => $startedAt->copy()->addMinutes($spec['duration']),
                    'status' => 'graded',
                    'total_marks' => $totalMarks,
                    'obtained_marks' => 0,
                    'percentage' => 0,
                    'needs_manual_grading' => false,
                    'graded_by' => $teacherId,
                    'graded_at' => $submittedAt->copy()->addMinutes(mt_rand(2, 25)),
                    'created_at' => $startedAt,
                    'updated_at' => now(),
                ]);
                $attemptCount++;

                foreach ($questionIds as $q) {
                    $isAuto = in_array($q['type'], ['mcq', 'true_false'], true) && $q['correct'] !== null;

                    if ($isAuto) {
                        $right = match ($band) {
                            'strong' => mt_rand(1, 100) <= 92,
                            'middling' => mt_rand(1, 100) <= 74,
                            default => mt_rand(1, 100) <= 45,
                        };
                        $selected = $right
                            ? $q['correct']
                            : $this->wrongOptionFor($q['options'], $q['correct'], $q['type']);
                        $awarded = $right ? $q['marks'] : 0;

                        DB::table('online_exam_answers')->insert([
                            'id' => (string) Str::ulid(),
                            'attempt_id' => $attemptId,
                            'question_id' => $q['id'],
                            'answer_text' => null,
                            'selected_option' => $selected,
                            'is_correct' => $right,
                            'marks_awarded' => $awarded,
                            'ai_feedback' => null,
                            'created_at' => $submittedAt,
                            'updated_at' => $submittedAt,
                        ]);
                    } else {
                        // Open response — AI-graded.
                        $ratio = match ($band) {
                            'strong' => mt_rand(82, 100) / 100,
                            'middling' => mt_rand(58, 80) / 100,
                            default => mt_rand(25, 52) / 100,
                        };
                        $awarded = (int) round($q['marks'] * $ratio);

                        DB::table('online_exam_answers')->insert([
                            'id' => (string) Str::ulid(),
                            'attempt_id' => $attemptId,
                            'question_id' => $q['id'],
                            'answer_text' => $this->writtenAnswerFor($band),
                            'selected_option' => null,
                            'is_correct' => null,
                            'marks_awarded' => $awarded,
                            'ai_feedback' => $this->aiFeedbackFor($band, $awarded, $q['marks']),
                            'created_at' => $submittedAt,
                            'updated_at' => $submittedAt,
                        ]);
                        $aiGradedAnswers++;
                    }

                    $obtained += $awarded;
                    $answerCount++;
                }

                // needs_manual_grading stays false because the AI grader has
                // scored every open response. A genuinely unhandled item would
                // set it true and surface the attempt in the teacher's queue.
                DB::table('online_exam_attempts')->where('id', $attemptId)->update([
                    'obtained_marks' => $obtained,
                    'percentage' => $totalMarks > 0 ? round($obtained / $totalMarks * 100, 2) : 0,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command?->line(
            "  ✓ online_exams seeded ({$examCount} exams, {$questionCount} questions, ai_grade_enabled on all)"
        );
        $this->command?->line(
            "  ✓ online_exam_attempts seeded ({$attemptCount} attempts, {$answerCount} answers of which {$aiGradedAnswers} AI-graded)"
        );
    }

    protected function teacherFor(string $subjectName): ?string
    {
        $label = $this->profile()->subjectTeacherLabels()[$subjectName] ?? null;

        return ($label ? ($this->staff->userIdByLabel[$label] ?? null) : null)
            ?? $this->staff->userIdByLabel['teacher_math']
            ?? $this->staff->userIdByLabel['admin'];
    }

    /** Pick a plausible wrong answer so incorrect attempts look real. */
    protected function wrongOptionFor(?array $options, ?string $correct, string $type): ?string
    {
        if ($type === 'true_false') {
            return $correct === 'true' ? 'false' : 'true';
        }

        $wrong = array_values(array_filter($options ?? [], fn ($o) => $o !== $correct));

        return $wrong === [] ? $correct : $wrong[mt_rand(0, count($wrong) - 1)];
    }

    protected function writtenAnswerFor(string $band): string
    {
        return match ($band) {
            'strong' => 'The two stages depend on each other because the first produces the energy carriers that the second spends. '
                . 'The light reactions generate ATP and NADPH and release oxygen from split water; the Calvin cycle then uses those carriers to fix carbon dioxide into sugar. '
                . 'If light were removed, the carriers would be used up within minutes and glucose production would fall to nothing, even though the enzymes of the second stage are still present and functional.',
            'middling' => 'The second stage needs the products of the first one. The light reactions make ATP and NADPH, and the Calvin cycle uses them to make glucose from carbon dioxide. '
                . 'Without light the first stage stops so the second one stops too and no more sugar is made.',
            default => 'Photosynthesis happens in two parts. The plant takes in light and carbon dioxide and makes sugar and oxygen. '
                . 'If there is no light then it cannot photosynthesise because light is one of the inputs.',
        };
    }

    /**
     * AI grader output, explicitly labelled.
     *
     * The label matters for the same reason it does on homework: whoever reads
     * a mark needs to know whether a person or a model wrote the comment.
     */
    protected function aiFeedbackFor(string $band, int $awarded, int $outOf): string
    {
        $body = match ($band) {
            'strong' => 'Full credit. You named both stages correctly, identified ATP and NADPH as the molecules passing between them, '
                . 'and your prediction about the effect of removing light is correct and well reasoned — you noted that the enzymes remain intact, '
                . 'which is the detail most answers miss.',
            'middling' => 'Most of the required content is present. You correctly identified the dependency and named the carrier molecules. '
                . 'Two things would have earned the remaining marks: stating explicitly that oxygen comes from splitting water, '
                . 'and saying roughly how quickly production falls once light is removed rather than only that it stops.',
            default => 'Partial credit. You have the overall inputs and outputs right, but the answer does not address the question asked, '
                . 'which was specifically about the dependency between the two stages. The carrier molecules are not named, '
                . 'and "light is one of the inputs" does not explain the mechanism. Review the lesson notes on the two stages and see your teacher.',
        };

        return "AI-graded ({$awarded}/{$outOf}), reviewed by the subject teacher. " . $body;
    }

    /**
     * Clamp a date to the current academic year — see the identical helper in
     * LecturesAndAssignmentsSeeder. A sat-and-graded assessment dated before
     * the first day of term is the giveaway that demo dates are arithmetic
     * around today rather than a real calendar.
     */
    protected function withinTerm(Carbon $date): Carbon
    {
        $start = $this->classes->yearStartDate;

        if ($start === '') {
            return $date;
        }

        $floor = Carbon::parse($start);

        return $date->lessThan($floor) ? $floor->copy()->addDays(mt_rand(1, 3)) : $date;
    }
}
