<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lectures, homework across its whole lifecycle, and AI tutor history.
 *
 * This is what fills the student portal. Without it the portal renders
 * correctly but every panel reads "nothing published yet".
 *
 * Three things get seeded:
 *
 *  1. study_materials — one row per profile lecture. source_type is
 *     'external_url' and the video is LINKED, never downloaded or re-hosted.
 *     The description carries the school's own lesson notes, which is what the
 *     AI tutor is grounded in.
 *
 *  2. homework_assignments + homework_submissions in every state the portal
 *     distinguishes: not yet submitted (some overdue), submitted and awaiting
 *     marking, marked by a teacher, and marked with AI assistance. A demo that
 *     only shows one state cannot show the workflow.
 *
 *  3. ai_conversations + ai_messages — saved tutor threads, so the AI feature
 *     looks lived-in on first load rather than presenting an empty box. Live
 *     prompts still hit the real model; this is history, not a substitute.
 */
class LecturesAndAssignmentsSeeder extends Seeder
{
    use UsesDemoProfile;

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
        public StudentsAndParentsSeeder $studentsAndParents,
    ) {}

    /** @var array<int,array{id:string,class_id:string,subject_id:?string,teacher_id:?string,title:string}> */
    protected array $seededLectures = [];

    public function run(): void
    {
        $this->seedLectures();
        $this->seedPractice();
        $this->seedAssignments();
        $this->seedRemainingSubjectHomework();
        $this->seedAiHistory();
    }

    // ── 1. Lectures ────────────────────────────────────────────────

    protected function seedLectures(): void
    {
        DB::table('study_materials')->delete();

        $lectures = $this->profile()->lectures();
        if ($lectures === []) {
            $this->command?->line('  ✓ study_materials skipped (profile has no verified catalogue)');

            return;
        }

        $count = 0;
        $skipped = 0;

        foreach ($lectures as [$level, $subjectName, $title, $videoId, $notes]) {
            $classId = $this->classes->classIdByNumber[$level] ?? null;
            $subjectId = $this->classes->subjectIdByName[$subjectName] ?? null;

            if ($classId === null) {
                $skipped++;
                continue;
            }

            $teacherId = $this->teacherFor($subjectName);
            $id = (string) Str::ulid();

            DB::table('study_materials')->insert([
                'id' => $id,
                'title' => $title,
                'description' => $notes,
                'category' => 'lecture',
                'class_id' => $classId,
                'section_id' => null,
                'subject_id' => $subjectId,
                'academic_year_id' => $this->classes->academicYearId,
                'teacher_id' => $teacherId,
                // 'link' (not 'external_url'): that is the value StudyMaterialResource
                // branches on, and source_type is varchar(10) so the longer string
                // would not fit anyway.
                'source_type' => 'link',
                'file_path' => null,
                'external_url' => 'https://www.youtube.com/watch?v=' . $videoId,
                'file_type' => 'video',
                'available_from' => Carbon::create(2026, 2, mt_rand(3, 26))->toDateString(),
                'is_published' => true,
                'download_count' => mt_rand(4, 61),
                'created_by' => $teacherId ?? $this->staff->userIdByLabel['admin'],
                'created_at' => Carbon::create(2026, 2, mt_rand(1, 27), mt_rand(8, 16)),
                'updated_at' => now(),
            ]);

            $this->seededLectures[] = [
                'id' => $id,
                'video_id' => $videoId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'title' => $title,
            ];
            $count++;
        }

        $msg = "  ✓ study_materials seeded ({$count} lectures, all 'link' source_type)";
        if ($skipped > 0) {
            $msg .= " — {$skipped} skipped, grade level not offered";
        }
        $this->command?->line($msg);
    }

    /** Resolve the subject's teacher, falling back to any teacher. */
    protected function teacherFor(string $subjectName): ?string
    {
        $label = $this->profile()->subjectTeacherLabels()[$subjectName] ?? null;

        return ($label ? ($this->staff->userIdByLabel[$label] ?? null) : null)
            ?? $this->staff->userIdByLabel['teacher_math']
            ?? null;
    }

    /**
     * Clamp a date to the current academic year.
     *
     * Dates are generated relative to today, but today can sit near the start
     * of a year, which would otherwise push "already graded" work back into
     * the previous summer.
     */
    protected function withinTerm(Carbon $date): Carbon
    {
        $start = $this->classes->yearStartDate;

        if ($start === '') {
            return $date;
        }

        $floor = Carbon::parse($start);

        return $date->lessThan($floor) ? $floor->copy()->addDays(mt_rand(1, 4)) : $date;
    }

    // ── 1b. Per-lecture practice quizzes and flashcards ────────────

    /**
     * Seed self-marking practice questions and revision cards per lecture.
     *
     * Questions go into exam_questions against study_material_id rather than a
     * parallel table, so a teacher can promote one into a real exam. Unlike the
     * online exam bank, every practice question carries a correct answer and an
     * explanation — practice marks itself, so an open-response item would just
     * stall the student with nothing to check against.
     */
    protected function seedPractice(): void
    {
        DB::table('lecture_quiz_attempts')->delete();
        DB::table('lecture_flashcards')->delete();
        DB::table('exam_questions')->whereNotNull('study_material_id')->delete();

        $bank = $this->profile()->lecturePractice();
        if ($bank === [] || $this->seededLectures === []) {
            $this->command?->line('  ✓ lecture practice skipped (none authored)');

            return;
        }

        $questions = 0;
        $cards = 0;
        $covered = 0;

        foreach ($this->seededLectures as $lecture) {
            $spec = $bank[$lecture['video_id']] ?? null;
            if ($spec === null) {
                continue;
            }
            $covered++;

            foreach ($spec['quiz'] as [$type, $text, $options, $correct, $explanation]) {
                DB::table('exam_questions')->insert([
                    'id' => (string) Str::ulid(),
                    'question_group_id' => null,
                    'study_material_id' => $lecture['id'],
                    'subject_id' => $lecture['subject_id'],
                    'type' => $type,
                    'difficulty' => 'easy',
                    'question_text' => $text,
                    'options' => $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                    'correct_answer' => $correct,
                    'explanation' => $explanation,
                    'marks' => 1,
                    'is_active' => true,
                    'created_by' => $lecture['teacher_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $questions++;
            }

            foreach ($spec['flashcards'] as $i => [$front, $back]) {
                DB::table('lecture_flashcards')->insert([
                    'id' => (string) Str::ulid(),
                    'study_material_id' => $lecture['id'],
                    'front' => $front,
                    'back' => $back,
                    'sort_order' => $i,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $cards++;
            }
        }

        $this->command?->line(
            "  ✓ lecture practice seeded ({$covered} lectures, {$questions} quiz questions, {$cards} flashcards)"
        );
    }

    // ── 2. Assignments across the whole lifecycle ──────────────────

    protected function seedAssignments(): void
    {
        DB::table('homework_submissions')->delete();
        DB::table('homework_assignments')->delete();

        if ($this->seededLectures === []) {
            $this->command?->line('  ✓ homework skipped (no lectures to attach to)');

            return;
        }

        // homework_assignments requires section_id, subject_id AND teacher_id
        // (all NOT NULL), so an assignment belongs to one section, not a whole
        // class. Group students by (class, section) and raise one assignment per
        // section — otherwise students in the other section of a grade would
        // never see the work.
        $studentsByClassSection = [];
        foreach ($this->studentsAndParents->studentRows as $s) {
            $studentsByClassSection[$s['class_id']][$s['section_id']][] = $s['id'];
        }

        $graderId = $this->staff->userIdByLabel['teacher_math'] ?? $this->staff->userIdByLabel['admin'];

        $assignments = 0;
        $submissions = 0;
        $graded = 0;
        $aiGraded = 0;
        $awaiting = 0;
        $open = 0;

        foreach ($this->seededLectures as $i => $lecture) {
            $sections = $studentsByClassSection[$lecture['class_id']] ?? [];
            if ($sections === [] || $lecture['subject_id'] === null || $lecture['teacher_id'] === null) {
                continue;
            }

            // Cycle the four states so every one is represented in the demo.
            $state = ['graded', 'ai_graded', 'awaiting', 'open'][$i % 4];
            $totalMarks = [20, 25, 30][$i % 3];

            // Past work is pulled forward so it cannot land before the first
            // day of term. Homework dated during the summer break, or inside a
            // year that has not started, is the kind of detail that makes a
            // demo fall apart the moment somebody reads the dates.
            $dueDate = $this->withinTerm(match ($state) {
                'open' => Carbon::now()->addDays(mt_rand(3, 16)),
                'awaiting' => Carbon::now()->subDays(mt_rand(2, 6)),
                default => Carbon::now()->subDays(mt_rand(9, 30)),
            });

            foreach ($sections as $sectionId => $students) {
            $assignmentId = (string) Str::ulid();
            DB::table('homework_assignments')->insert([
                'id' => $assignmentId,
                'class_id' => $lecture['class_id'],
                'section_id' => $sectionId,
                'subject_id' => $lecture['subject_id'],
                'teacher_id' => $lecture['teacher_id'],
                'title' => $this->assignmentTitleFor($lecture['title'], $state),
                'description' => $this->assignmentBriefFor($lecture['title'], $totalMarks),
                'due_date' => $dueDate->toDateString(),
                'attachment_path' => null,
                'type' => 'homework',
                'total_marks' => $totalMarks,
                'created_at' => $this->withinTerm($dueDate->copy()->subDays(10)),
                'updated_at' => now(),
            ]);
            $assignments++;

            if ($state === 'open') {
                $open++;
                continue; // nothing submitted yet — this is the "to do" pile
            }


            // Most of the cohort submits; a couple do not, which is realistic and
            // makes the teacher-side "who is missing" view meaningful.
            //
            // Rotate before slicing. Taking the first N every time meant the LAST
            // student in each section never submitted anything at all — and in a
            // small demo section that is one specific student with a permanently
            // empty assignments page. Rotating spreads the gaps so every student
            // has a mix of submitted and missing work.
            $rotated = $students;
            if (count($rotated) > 1) {
                $offset = $i % count($rotated);
                $rotated = array_merge(array_slice($rotated, $offset), array_slice($rotated, 0, $offset));
            }
            $submitters = array_slice($rotated, 0, max(1, (int) round(count($rotated) * 0.85)));

            foreach ($submitters as $studentId) {
                $submittedAt = $dueDate->copy()->subDays(mt_rand(0, 3))->setTime(mt_rand(9, 22), mt_rand(0, 59));
                $row = [
                    'id' => (string) Str::ulid(),
                    'homework_id' => $assignmentId,
                    'student_id' => $studentId,
                    'submission_text' => $this->submissionTextFor($lecture['title']),
                    'attachment_path' => null,
                    'submitted_at' => $submittedAt,
                    'total_marks' => $totalMarks,
                    'created_at' => $submittedAt,
                    'updated_at' => now(),
                ];

                if ($state === 'awaiting') {
                    $awaiting++;
                } else {
                    // Marks skewed toward the upper half, as real class marks are.
                    $marks = (int) round($totalMarks * (mt_rand(58, 100) / 100));
                    $row['marks_obtained'] = $marks;
                    $row['grade'] = $this->letterFor($marks / $totalMarks * 100);
                    $row['graded_by'] = $graderId;
                    $row['graded_at'] = $submittedAt->copy()->addDays(mt_rand(1, 5));
                    $row['feedback'] = $state === 'ai_graded'
                        ? $this->aiFeedbackFor($marks, $totalMarks)
                        : $this->teacherFeedbackFor($marks, $totalMarks);
                    $graded++;
                    if ($state === 'ai_graded') {
                        $aiGraded++;
                    }
                }

                DB::table('homework_submissions')->insert($row);
                $submissions++;
            }
            } // end section loop
        }

        $this->command?->line(
            "  ✓ homework_assignments seeded ({$assignments}: {$open} open, states cycled)"
        );
        $this->command?->line(
            "  ✓ homework_submissions seeded ({$submissions}: {$graded} marked of which {$aiGraded} AI-assisted, {$awaiting} awaiting)"
        );
    }

    protected function assignmentTitleFor(string $lectureTitle, string $state): string
    {
        $stem = Str::limit(preg_replace('/[:—].*$/u', '', $lectureTitle) ?: $lectureTitle, 46, '');

        return trim($stem) . ' — ' . match ($state) {
            'open' => 'practice set',
            'awaiting' => 'written response',
            'ai_graded' => 'short-answer quiz',
            default => 'problem set',
        };
    }

    protected function assignmentBriefFor(string $lectureTitle, int $marks): string
    {
        return "Based on the lecture \"{$lectureTitle}\".\n\n"
            . "Answer in your own words. Full sentences, and show your reasoning where a question asks for it. "
            . "You may re-watch the lecture and use your notes.\n\n"
            . "Total: {$marks} marks. Late work is accepted for up to three days at a reduced maximum.";
    }

    protected function submissionTextFor(string $lectureTitle): string
    {
        $openers = [
            'From the lecture, the main idea is that',
            'My understanding of this topic is that',
            'Working through the examples, I found that',
            'The part that made this click for me was that',
        ];

        return $this->profile()->pick($openers) . ' the process described in "'
            . Str::limit($lectureTitle, 48) . '" follows a clear order, and each step depends on the one before it. '
            . "I worked through the examples from the lesson and checked my answers against the notes. "
            . "The step I found hardest was keeping track of which stage produces which result, so I wrote it out twice to be sure.";
    }

    protected function letterFor(float $percent): string
    {
        foreach ($this->profile()->gradeRules() as [$grade, $min, $max, , ]) {
            if ($percent >= $min && $percent <= $max + 0.999) {
                return $grade;
            }
        }

        return 'F';
    }

    protected function teacherFeedbackFor(int $marks, int $total): string
    {
        $pct = $marks / $total * 100;

        if ($pct >= 90) {
            return 'Excellent work. Your explanation of the sequence is precise and you justified each step. Keep doing exactly this.';
        }
        if ($pct >= 75) {
            return 'Good, solid answer. The reasoning is sound; be a little more explicit about why the second stage depends on the first and this would be full marks.';
        }
        if ($pct >= 60) {
            return 'You have the general idea but some of the detail is muddled. Re-watch the middle section of the lecture and re-read your notes on the order of steps.';
        }

        return 'This needs another attempt. Come and see me — I would rather work through it with you than have you guess. Bring your notes.';
    }

    /**
     * AI-assisted feedback, explicitly labelled as such.
     *
     * The label is not decoration. A parent or auditor looking at a mark needs
     * to know whether a human or a model produced the comment, and a school
     * presenting AI marking without disclosure would be storing up a problem.
     */
    protected function aiFeedbackFor(int $marks, int $total): string
    {
        $pct = $marks / $total * 100;

        $body = match (true) {
            $pct >= 90 => 'Your answer covers every required point and the order of reasoning is correct. '
                . 'You correctly distinguished the stage that captures energy from the stage that uses it. '
                . 'Nothing to correct.',
            $pct >= 75 => 'You identified the main steps correctly. Two points were only partly addressed: '
                . 'the role of the intermediate products, and why the sequence cannot run in reverse. '
                . 'Adding one sentence on each would have earned full marks.',
            $pct >= 60 => 'The core idea is present but several details are inaccurate or missing. '
                . 'Specifically, the inputs and outputs are transposed in your second paragraph. '
                . 'Review that section of the lecture notes and resubmit if your teacher allows it.',
            default => 'This answer does not yet demonstrate the required understanding. '
                . 'Key terms are used interchangeably when they mean different things. '
                . 'Your teacher has been notified so they can go through it with you directly.',
        };

        return 'AI-assisted marking, reviewed by the subject teacher. ' . $body;
    }

    // ── 2b. Homework for the rest of the timetable ─────────────────

    /**
     * Give every class/subject on the timetable some set work.
     *
     * The assignments above hang off lectures, which only exist for the
     * fourteen class/subject pairs that have recorded material — grades 6 to
     * 12, and only some of their subjects. That left Kindergarten through
     * Grade 5 with no homework at all, so half the parents in the school
     * opened the portal to an empty page. This fills the gaps from the
     * timetable itself: one marked task behind and one still due, per section.
     */
    protected function seedRemainingSubjectHomework(): void
    {
        $tasks = $this->profile()->homeworkTasks();

        if ($tasks === []) {
            return;
        }

        $covered = DB::table('homework_assignments')
            ->selectRaw("class_id || '|' || subject_id as pair")
            ->distinct()
            ->pluck('pair')
            ->flip();

        $rows = DB::table('class_subjects as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->join('subjects as s', 's.id', '=', 'cs.subject_id')
            ->whereNotNull('cs.teacher_id')
            ->select('cs.class_id', 'cs.subject_id', 'cs.teacher_id', 'c.name as class_name', 's.name as subject_name')
            ->get();

        $studentsByClassSection = [];
        foreach ($this->studentsAndParents->studentRows as $student) {
            $studentsByClassSection[$student['class_id']][$student['section_id']][] = $student['id'];
        }

        $created = 0;
        $submissions = 0;
        $n = 0;

        foreach ($rows as $row) {
            if ($covered->has($row->class_id . '|' . $row->subject_id)) {
                continue;
            }

            $band = $this->bandFor($row->class_name);
            $pool = $tasks[$row->subject_name][$band] ?? null;

            if ($pool === null) {
                continue;
            }

            foreach (($studentsByClassSection[$row->class_id] ?? []) as $sectionId => $students) {
                foreach ([['graded', 0], ['open', 1]] as [$state, $which]) {
                    [$title, $brief] = $pool[$which % count($pool)];
                    $totalMarks = [10, 15, 20][$n % 3];

                    $dueDate = $this->withinTerm($state === 'open'
                        ? Carbon::now()->addDays(mt_rand(2, 12))
                        : Carbon::now()->subDays(mt_rand(4, 20)));

                    $assignmentId = (string) Str::ulid();

                    DB::table('homework_assignments')->insert([
                        'id' => $assignmentId,
                        'class_id' => $row->class_id,
                        'section_id' => $sectionId,
                        'subject_id' => $row->subject_id,
                        'teacher_id' => $row->teacher_id,
                        'title' => $title,
                        'description' => $brief,
                        'due_date' => $dueDate->toDateString(),
                        'attachment_path' => null,
                        'type' => 'homework',
                        'total_marks' => $totalMarks,
                        'created_at' => $this->withinTerm($dueDate->copy()->subDays(7)),
                        'updated_at' => now(),
                    ]);
                    $created++;
                    $n++;

                    if ($state === 'open') {
                        continue;
                    }

                    $rotated = $students;
                    if (count($rotated) > 1) {
                        $offset = $n % count($rotated);
                        $rotated = array_merge(array_slice($rotated, $offset), array_slice($rotated, 0, $offset));
                    }

                    foreach (array_slice($rotated, 0, max(1, (int) round(count($rotated) * 0.9))) as $studentId) {
                        $submittedAt = $dueDate->copy()->subDays(mt_rand(0, 2))->setTime(mt_rand(16, 21), mt_rand(0, 59));
                        $marks = (int) round($totalMarks * (mt_rand(60, 100) / 100));

                        DB::table('homework_submissions')->insert([
                            'id' => (string) Str::ulid(),
                            'homework_id' => $assignmentId,
                            'student_id' => $studentId,
                            'submission_text' => 'Submitted: ' . $title . '.',
                            'attachment_path' => null,
                            'submitted_at' => $submittedAt,
                            'total_marks' => $totalMarks,
                            'marks_obtained' => $marks,
                            'grade' => $this->letterFor($marks / $totalMarks * 100),
                            'graded_at' => $submittedAt->copy()->addDays(mt_rand(1, 4)),
                            'graded_by' => $row->teacher_id,
                            'created_at' => $submittedAt,
                            'updated_at' => now(),
                        ]);
                        $submissions++;
                    }
                }
            }
        }

        $this->command?->line("  ✓ timetable homework seeded ({$created} assignments, {$submissions} marked submissions)");
    }

    /** Which authored band a class name falls into. */
    protected function bandFor(string $className): string
    {
        $grade = (int) (preg_replace('/\D+/', '', $className) ?: 0);

        return match (true) {
            $grade >= 9 => 'high',
            $grade >= 6 => 'middle',
            default => 'elementary',
        };
    }

    // ── 3. AI tutor history ────────────────────────────────────────

    /**
     * Seed a few saved tutor threads so the AI panel has history.
     *
     * Titled exactly as MyLectures::conversation() titles them ("Lecture: …"),
     * so an existing thread is picked up rather than a duplicate being created
     * the first time a student asks something.
     */
    protected function seedAiHistory(): void
    {
        DB::table('ai_messages')->delete();
        DB::table('ai_conversations')->delete();

        if ($this->seededLectures === []) {
            $this->command?->line('  ✓ ai history skipped (no lectures)');

            return;
        }

        // Pair each lecture with a student in that lecture's class.
        $studentUserByClass = [];
        foreach ($this->studentsAndParents->studentRows as $s) {
            if (! empty($s['school_user_id'])) {
                $studentUserByClass[$s['class_id']][] = $s['school_user_id'];
            }
        }

        $threads = 0;
        $messages = 0;

        foreach (array_slice($this->seededLectures, 0, 6) as $lecture) {
            $candidates = $studentUserByClass[$lecture['class_id']] ?? [];
            if ($candidates === []) {
                continue;
            }
            $userId = $candidates[0];

            $conversationId = (string) Str::ulid();
            DB::table('ai_conversations')->insert([
                'id' => $conversationId,
                'school_user_id' => $userId,
                'role_when_created' => 'STUDENT',
                'title' => 'Lecture: ' . Str::limit($lecture['title'], 60),
                'created_at' => Carbon::now()->subDays(mt_rand(2, 21)),
                'updated_at' => now(),
            ]);
            $threads++;

            $at = Carbon::now()->subDays(mt_rand(2, 21))->setTime(mt_rand(16, 21), mt_rand(0, 59));
            foreach ($this->sampleExchange($lecture['title']) as $turn) {
                DB::table('ai_messages')->insert([
                    'id' => (string) Str::ulid(),
                    'conversation_id' => $conversationId,
                    'role' => $turn[0],
                    'content' => $turn[1],
                    'created_at' => $at,
                ]);
                $at = $at->copy()->addSeconds(mt_rand(20, 240));
                $messages++;
            }
        }

        $this->command?->line("  ✓ ai_conversations seeded ({$threads} threads, {$messages} messages)");
    }

    /**
     * A short, plausible tutor exchange.
     *
     * The second answer deliberately declines to do the student's graded work,
     * because that is the behaviour MyLectures' system prompt actually asks for
     * — seeded history that contradicted the live prompt would misrepresent the
     * product.
     *
     * @return array<int,array{0:string,1:string}>
     */
    protected function sampleExchange(string $lectureTitle): array
    {
        return [
            ['user', 'Can you explain the part in the middle again? I got lost around the second stage.'],
            ['assistant', "Of course. Think of it in two halves.\n\n"
                . "The first half captures energy and stores it in carrier molecules. It does not make the final product — that is the step people usually mix up.\n\n"
                . "The second half spends that stored energy to assemble the final product step by step.\n\n"
                . "So the order matters: the second half cannot start until the first has produced its carriers. If you remember \"capture, then build\", the sequence tends to stay straight."],
            ['user', 'Can you just give me the answers to question 3 on the homework?'],
            ['assistant', "I am not going to answer your homework question for you — that is your work to do, and you will not learn much from copying it.\n\n"
                . "What I can do is walk you through the method. Question 3 is asking you to apply the same two-stage sequence from the lecture. Start by writing down what you are given and what is being asked, then identify which stage of the process each belongs to.\n\n"
                . "Try the first line and tell me what you get, and I will tell you whether you are on the right track."],
            ['user', 'That helps, thanks. So the notes say the stages depend on each other — is that always true?'],
            ['assistant', "Within what these notes cover, yes: the second stage depends on the products of the first, so it cannot proceed without them.\n\n"
                . "Whether there are exceptions in more advanced cases is beyond what this lecture covers, so I would not want to guess. That is a good question to put to your teacher — it is exactly the kind of thing they will be pleased you asked."],
        ];
    }
}
