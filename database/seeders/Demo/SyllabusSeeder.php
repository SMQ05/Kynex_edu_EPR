<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The curriculum spine: syllabi, weekly topics and teacher lesson plans.
 *
 * Everything else in the demo is an event — a lecture uploaded, a homework
 * marked, a fee paid. A syllabus is the only thing that says what the school
 * intends to teach and how far along it is, which is what makes a teacher's
 * plan, a student's learning path and a parent's "is my child keeping up?"
 * answerable from the same data instead of three unrelated screens.
 *
 * Week numbers are counted from the first day of the academic year and each
 * topic is pinned to the Monday of its week, so the plan tracks the real
 * calendar. Topics before this week are completed, this week's are in
 * progress, and the rest are planned — which is exactly the shape a term plan
 * has at any moment during a year.
 */
class SyllabusSeeder extends Seeder
{
    use UsesDemoProfile;

    /** Weeks of teaching each syllabus topic is allotted. */
    private const WEEKS_PER_UNIT = 3;

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
    ) {}

    public function run(): void
    {
        $plans = $this->profile()->syllabusPlans();

        if ($plans === []) {
            $this->command?->line('  ✓ syllabi skipped (no curriculum authored for this profile)');

            return;
        }

        DB::table('lesson_plans')->delete();
        DB::table('lessons')->delete();
        DB::table('syllabus_topics')->delete();
        DB::table('syllabi')->delete();
        DB::table('study_materials')->update(['syllabus_topic_id' => null]);

        $yearId = $this->classes->academicYearId;
        $yearStart = Carbon::parse($this->classes->yearStartDate)->startOfWeek();
        $currentWeek = $this->weekNumber($yearStart, Carbon::today());

        $syllabi = 0;
        $topics = 0;
        $linked = 0;
        $lessonPlans = 0;
        $skipped = [];

        foreach ($plans as $key => $plan) {
            [$className, $subjectName] = array_pad(explode('|', $key, 2), 2, '');

            $classId = DB::table('classes')->where('name', $className)->value('id');
            $subjectId = DB::table('subjects')->where('name', $subjectName)->value('id');

            if (! $classId || ! $subjectId) {
                $skipped[] = $key;

                continue;
            }

            // Prefer whoever already teaches this pair — the lecture library is
            // the most reliable record of that, because it was seeded from the
            // same timetable the classes seeder built.
            $teacherId = DB::table('study_materials')
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->whereNotNull('teacher_id')
                ->value('teacher_id')
                ?? $this->staff->userIdByLabel['principal']
                ?? $this->staff->userIdByLabel['admin'];

            $syllabusId = (string) Str::ulid();

            DB::table('syllabi')->insert([
                'id' => $syllabusId,
                'class_id' => $classId,
                'section_id' => null,
                'subject_id' => $subjectId,
                'academic_year_id' => $yearId,
                'teacher_id' => $teacherId,
                'title' => $plan['title'],
                'description' => $plan['description'],
                'status' => 'published',
                'created_at' => $yearStart->copy()->subDays(14),
                'updated_at' => now(),
            ]);
            $syllabi++;

            $lessonId = (string) Str::ulid();
            DB::table('lessons')->insert([
                'id' => $lessonId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'section_id' => null,
                'academic_year_id' => $yearId,
                'teacher_id' => $teacherId,
                'title' => $plan['title'],
                'code' => $this->lessonCode($className, $subjectName),
                'description' => $plan['description'],
                'sort_order' => 0,
                'is_active' => true,
                'created_by' => $teacherId,
                'created_at' => $yearStart->copy()->subDays(14),
                'updated_at' => now(),
            ]);

            foreach (array_values($plan['topics']) as $index => $topic) {
                // Each topic is a unit of work, not a single week. Twelve units
                // across a 36-week year is three weeks each, which is what a
                // real scheme of work looks like; numbering them one per week
                // would have the entire year taught by October.
                $week = ($index * self::WEEKS_PER_UNIT) + 1;
                $lastWeek = $week + self::WEEKS_PER_UNIT - 1;
                $plannedDate = $yearStart->copy()->addWeeks($week - 1);

                $status = match (true) {
                    $lastWeek < $currentWeek => 'completed',
                    $week <= $currentWeek => 'in_progress',
                    default => 'planned',
                };

                $topicId = (string) Str::ulid();

                DB::table('syllabus_topics')->insert([
                    'id' => $topicId,
                    'syllabus_id' => $syllabusId,
                    'title' => $topic['title'],
                    'description' => $topic['description'],
                    'week_number' => $week,
                    'planned_date' => $plannedDate->toDateString(),
                    'completed_at' => $status === 'completed'
                    ? $plannedDate->copy()->addWeeks(self::WEEKS_PER_UNIT)->subDays(3)->toDateString()
                    : null,
                    'status' => $status,
                    'sort_order' => $index,
                    'created_at' => $yearStart->copy()->subDays(14),
                    'updated_at' => now(),
                ]);
                $topics++;

                if (isset($topic['match'])) {
                    $linked += $this->attachLecture($topicId, $classId, $subjectId, $topic['match']);
                }

                // Lesson plans exist for the weeks a teacher has actually
                // reached, plus the one being prepared. Writing a full plan for
                // every week of the year would be pure filler — no teacher has
                // week 30 written up in week 2.
                if ($week <= $currentWeek + self::WEEKS_PER_UNIT) {
                    DB::table('lesson_plans')->insert([
                        'id' => (string) Str::ulid(),
                        'lesson_id' => $lessonId,
                        'syllabus_topic_id' => $topicId,
                        'teacher_id' => $teacherId,
                        'title' => $topic['title'],
                        'plan_date' => $plannedDate->toDateString(),
                        'week_number' => $week,
                        'duration_minutes' => 50,
                        'objectives' => 'Students will be able to: ' . $topic['objective'],
                        'activities' => $this->activitiesFor($subjectName, $topic['title']),
                        'teaching_resources' => $this->resourcesFor($subjectName),
                        'assessment' => $this->assessmentFor($topic['objective']),
                        'homework' => $this->homeworkFor($topic['title']),
                        'notes' => $topic['description'],
                        'status' => $status,
                        'created_by' => $teacherId,
                        'created_at' => $plannedDate->copy()->subDays(7),
                        'updated_at' => now(),
                    ]);
                    $lessonPlans++;
                }
            }
        }

        $this->command?->line(
            "  ✓ syllabi seeded ({$syllabi} courses, {$topics} weekly topics, {$lessonPlans} lesson plans, "
            . "{$linked} lectures placed on the plan; week {$currentWeek} of the year)"
        );

        if ($skipped !== []) {
            $this->command?->warn('  ⚠ no class/subject match for: ' . implode(', ', $skipped));
        }
    }

    /** Attach any lecture in this class+subject whose title contains $needle. */
    protected function attachLecture(string $topicId, string $classId, string $subjectId, string $needle): int
    {
        return DB::table('study_materials')
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('title', 'ilike', '%' . $needle . '%')
            ->update(['syllabus_topic_id' => $topicId, 'updated_at' => now()]);
    }

    /** 1-based week of the year for a date, floored at 1. */
    protected function weekNumber(Carbon $yearStart, Carbon $date): int
    {
        return max(1, (int) $yearStart->diffInWeeks($date) + 1);
    }

    protected function lessonCode(string $className, string $subjectName): string
    {
        $grade = preg_replace('/\D+/', '', $className) ?: 'K';
        $subject = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subjectName) ?: 'GEN', 0, 4));

        return "{$subject}-{$grade}";
    }

    protected function activitiesFor(string $subject, string $topic): string
    {
        $core = match ($subject) {
            'Mathematics' => "Worked example on the board, then paired practice on graduated problems.\nStudents put one solution on the board and the class checks each step.",
            'Physics', 'Science' => "Demonstration and prediction before explanation — students commit to an answer first.\nSmall-group practical, then whole-class reconciliation of results.",
            'Biology' => "Annotated diagram built up on the board as the mechanism is explained.\nStudents complete the diagram from memory before leaving.",
            'Computer Science' => "Trace the method by hand on paper before any code is written.\nPair programming, then a short code review against another pair.",
            'U.S. History' => "Short primary source read in pairs with guided questions.\nStructured discussion, then a written paragraph taking a position.",
            default => "Guided introduction, paired task, then whole-class review.",
        };

        return "Starter (5 min): recall questions on the previous lesson.\n{$core}\nPlenary (5 min): three questions on {$topic} answered on exit slips.";
    }

    protected function resourcesFor(string $subject): string
    {
        return match ($subject) {
            'Mathematics' => 'Whiteboard, graded problem set, graphing display.',
            'Physics', 'Science' => 'Lab apparatus, measurement equipment, results sheet.',
            'Biology' => 'Microscopes and prepared slides, labelled diagram handout.',
            'Computer Science' => 'Lab machines, starter code, printed tracing tables.',
            'U.S. History' => 'Primary source pack, timeline display, discussion prompts.',
            default => 'Course textbook, worksheet, projector.',
        };
    }

    protected function assessmentFor(string $objective): string
    {
        return "Exit slip against the objective: {$objective}\nCirculating checks during paired work; misconceptions logged for the next lesson.";
    }

    protected function homeworkFor(string $topic): string
    {
        return "Consolidation set on {$topic}, plus the lecture recording and its practice quiz for anyone who wants another pass.";
    }
}
