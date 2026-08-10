<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The weekly timetable for every class.
 *
 * DemoTenantSeeder wipes class_routines and, until now, nothing rebuilt it —
 * so the demo school ran with no timetable at all. That is not a cosmetic gap:
 * without it a student cannot be told which lesson they are in, which is the
 * first thing a school portal should know, and the "right now" panel on the
 * dashboard has nothing to show.
 *
 * Periods are built from the subjects each class actually takes, using the
 * teacher already assigned to that pair, so the timetable agrees with the
 * lecture library and the syllabus rather than inventing a parallel schedule.
 */
class TimetableSeeder extends Seeder
{
    use UsesDemoProfile;

    /** Teaching days. A US school week, Monday to Friday. */
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    /** First bell, and the length of a lesson. */
    private const DAY_STARTS = '08:00';

    private const PERIOD_MINUTES = 45;

    /** Periods after which a break falls, and how long it lasts. */
    private const BREAKS = [2 => ['Morning break', 15], 5 => ['Lunch', 40]];

    private const PERIODS_PER_DAY = 7;

    public function __construct(
        public ClassesSeeder $classes,
    ) {}

    public function run(): void
    {
        DB::table('class_routines')->delete();

        $yearId = $this->classes->academicYearId;
        $campusId = $this->classes->mainCampusId;

        // Every class/subject pair with its assigned teacher — the same source
        // the syllabus and homework seeders read, so the three agree.
        $pairs = DB::table('class_subjects')
            ->whereNotNull('teacher_id')
            ->select('class_id', 'subject_id', 'teacher_id')
            ->get()
            ->groupBy('class_id');

        $sectionsByClass = DB::table('sections')
            ->select('id', 'class_id', 'name')
            ->get()
            ->groupBy('class_id');

        $rows = [];
        $breaks = 0;

        foreach ($pairs as $classId => $subjects) {
            $subjects = $subjects->values();

            if ($subjects->isEmpty()) {
                continue;
            }

            $grade = preg_replace('/\D+/', '', (string) DB::table('classes')->where('id', $classId)->value('name')) ?: '1';

            foreach (($sectionsByClass[$classId] ?? collect([null])) as $section) {
                // Offset each section so two sections of a grade are not both
                // in the lab at the same time.
                $offset = $section ? (int) ord(substr((string) $section->name, -1)) : 0;
                $slot = 0;

                foreach (self::DAYS as $dayIndex => $day) {
                    $cursor = Carbon::createFromTimeString(self::DAY_STARTS);

                    for ($period = 1; $period <= self::PERIODS_PER_DAY; $period++) {
                        $subject = $subjects[($slot + $offset + $dayIndex) % $subjects->count()];
                        $slot++;

                        $end = $cursor->copy()->addMinutes(self::PERIOD_MINUTES);

                        $rows[] = [
                            'id' => (string) Str::ulid(),
                            'class_id' => $classId,
                            'section_id' => $section?->id,
                            'academic_year_id' => $yearId,
                            'campus_id' => $campusId,
                            'day_of_week' => $day,
                            'period_number' => $period,
                            'subject_id' => $subject->subject_id,
                            'teacher_id' => $subject->teacher_id,
                            'room_number' => 'Room ' . $grade . str_pad((string) (($period % 4) + 1), 2, '0', STR_PAD_LEFT),
                            'start_time' => $cursor->format('H:i:s'),
                            'end_time' => $end->format('H:i:s'),
                            'is_break' => false,
                            'break_label' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $cursor = $end;

                        if (isset(self::BREAKS[$period])) {
                            [$label, $minutes] = self::BREAKS[$period];
                            $breakEnd = $cursor->copy()->addMinutes($minutes);

                            $rows[] = [
                                'id' => (string) Str::ulid(),
                                'class_id' => $classId,
                                'section_id' => $section?->id,
                                'academic_year_id' => $yearId,
                                'campus_id' => $campusId,
                                'day_of_week' => $day,
                                'period_number' => $period + 100, // keeps breaks out of the lesson numbering
                                'subject_id' => null,
                                'teacher_id' => null,
                                'room_number' => null,
                                'start_time' => $cursor->format('H:i:s'),
                                'end_time' => $breakEnd->format('H:i:s'),
                                'is_break' => true,
                                'break_label' => $label,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $breaks++;
                            $cursor = $breakEnd;
                        }
                    }
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_routines')->insert($chunk);
        }

        $lessons = count($rows) - $breaks;
        $this->command?->line(
            "  ✓ class_routines seeded ({$lessons} lessons + {$breaks} breaks, "
            . self::PERIODS_PER_DAY . ' periods x ' . count(self::DAYS) . ' days per section)'
        );
    }
}
