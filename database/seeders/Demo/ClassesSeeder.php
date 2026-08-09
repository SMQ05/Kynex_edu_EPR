<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * c. ClassesSeeder
 *
 * Wipes any pre-existing classes/sections/subjects/class_subjects rows
 * (handled in the orchestrator's wipe phase, but this seeder also runs
 * a defensive delete for fresh runs that skipped the wipe). Then seeds:
 *  - 10 classes (Class 1..Class 10)
 *  - 15 sections (classes 1-5 have A and B; classes 6-10 single A)
 *  - 13 subjects covering primary + secondary
 *  - class_subjects mapping subjects per class with a teacher assigned
 *
 * Class teacher per section is assigned round-robin across the 10
 * teachers seeded in StaffSeeder (each teacher class-teaches 1-2
 * sections).
 *
 * Reads StaffSeeder.userIdByLabel for teacher IDs and the academic_years
 * row from the DB.
 */
class ClassesSeeder extends Seeder
{
    use UsesDemoProfile;

    public string $academicYearId = '';

    /** The year before the current one, when the profile defines history. */
    public string $previousAcademicYearId = '';

    /** First day of the current year — the floor for any seeded date. */
    public string $yearStartDate = '';
    public string $mainCampusId = '';

    /** @var array<int, string> classNumber => class.id */
    public array $classIdByNumber = [];

    /** @var array<string, array{id:string, class_id:string, class_number:int}> sectionKey 'N-A' => row */
    public array $sectionByKey = [];

    /** @var array<string, string> subject name => subject.id */
    public array $subjectIdByName = [];

    /**
     * Per-class subject lists per spec.
     *
     * @var array<int, list<string>>
     */
    public const CLASS_SUBJECTS = [
        1 => ['Math', 'English', 'Urdu', 'Science', 'Islamiyat'],
        2 => ['Math', 'English', 'Urdu', 'Science', 'Islamiyat'],
        3 => ['Math', 'English', 'Urdu', 'Science', 'Islamiyat'],
        4 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
        5 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
        6 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
        7 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
        8 => ['Math', 'English', 'Urdu', 'Physics', 'Chemistry', 'Biology', 'Computer', 'Islamiyat'],
        9 => ['Math', 'English', 'Urdu', 'Physics', 'Chemistry', 'Biology', 'Computer', 'Islamiyat'],
        10 => ['Math', 'English', 'Urdu', 'Physics', 'Chemistry', 'Biology', 'Computer', 'Islamiyat'],
    ];

    /** subject name => teacher label in StaffSeeder.userIdByLabel */
    public const SUBJECT_TEACHER_LABEL = [
        'Math' => 'subject:Math',
        'English' => 'subject:English',
        'Urdu' => 'subject:Urdu',
        'Science' => 'subject:Science',
        'Physics' => 'subject:Science',          // bio-teacher proxies physics in primary demo
        'Chemistry' => 'subject:Science',
        'Biology' => 'subject:Science',
        'Social Studies' => 'subject:Social Studies',
        'Islamiyat' => 'subject:Islamiyat',
        'Quran' => 'subject:Quran',
        'Computer' => 'subject:Computer',
        'Arts' => 'subject:Arts',
        'Physical Education' => 'subject:Physical Education',
    ];

    public function __construct(
        public StaffSeeder $staff,
    ) {}

    public function run(): void
    {
        $this->mainCampusId = (string) DB::table('campuses')
            ->where('is_main_campus', true)
            ->value('id');

        $this->academicYearId = (string) DB::table('academic_years')
            ->where('is_current', true)
            ->orderBy('created_at')
            ->value('id');

        if (! $this->academicYearId) {
            throw new \RuntimeException('No current academic_year row exists.');
        }

        $this->yearStartDate = (string) DB::table('academic_years')
            ->where('id', $this->academicYearId)
            ->value('start_date');

        $this->previousAcademicYearId = (string) DB::table('academic_years')
            ->where('is_current', false)
            ->where('end_date', '<=', $this->yearStartDate)
            ->orderByDesc('end_date')
            ->value('id');

        $this->seedClasses();
        $this->seedSections();
        $this->seedSubjects();
        $this->seedClassSubjects();
    }

    protected function seedClasses(): void
    {
        DB::table('classes')->delete();

        foreach ($this->profile()->gradeLevels() as $n => $levelName) {
            $id = (string) Str::ulid();
            DB::table('classes')->insert([
                'id' => $id,
                'name' => $levelName,
                'numeric_level' => $n,
                'sort_order' => $n,
                'description' => "Grade {$n} cohort",
                'campus_id' => $this->mainCampusId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->classIdByNumber[$n] = $id;
        }
        $this->command?->line('  ✓ classes seeded (10)');
    }

    protected function seedSections(): void
    {
        DB::table('sections')->delete();

        // Round-robin teachers for class teacher assignments.
        $teacherLabels = [
            'subject:Math', 'subject:English', 'subject:Urdu', 'subject:Science',
            'subject:Social Studies', 'subject:Islamiyat', 'subject:Computer',
            'subject:Arts', 'subject:Physical Education', 'subject:Quran',
        ];
        $teacherIds = [];
        foreach ($teacherLabels as $label) {
            $tid = $this->staff->userIdByLabel[$label] ?? null;
            if ($tid) {
                $teacherIds[] = $tid;
            }
        }
        if (empty($teacherIds)) {
            throw new \RuntimeException('No teachers available for class-teacher assignment.');
        }

        $rotor = 0;
        $count = 0;
        foreach (array_keys($this->profile()->gradeLevels()) as $n) {
            $sectionsForClass = $this->profile()->sectionsForLevel($n);
            foreach ($sectionsForClass as $name) {
                $id = (string) Str::ulid();
                $teacherId = $teacherIds[$rotor++ % count($teacherIds)];
                DB::table('sections')->insert([
                    'id' => $id,
                    'class_id' => $this->classIdByNumber[$n],
                    'name' => $name,
                    'capacity' => 40,
                    'class_teacher_id' => $teacherId,
                    'room_number' => sprintf('%d%02d', $n, $rotor),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->sectionByKey[$n . '-' . $name] = [
                    'id' => $id,
                    'class_id' => $this->classIdByNumber[$n],
                    'class_number' => $n,
                    'class_teacher_id' => $teacherId,
                ];
                $count++;
            }
        }
        $this->command?->line("  ✓ sections seeded ({$count})");
    }

    protected function seedSubjects(): void
    {
        DB::table('class_subjects')->delete();
        DB::table('subjects')->delete();

        $rows = $this->profile()->subjects();
        foreach ($rows as [$name, $code, $color]) {
            $id = (string) Str::ulid();
            DB::table('subjects')->insert([
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'subject_type' => 'theory',
                'credit_hours' => 1,
                'color' => $color,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->subjectIdByName[$name] = $id;
        }
        $this->command?->line('  ✓ subjects seeded (' . count($rows) . ')');
    }

    /**
     * Per-class subject mapping. One class_subjects row per (class, subject)
     * — applies to both sections of a class. Teacher comes from
     * SUBJECT_TEACHER_LABEL via StaffSeeder.userIdByLabel.
     */
    protected function seedClassSubjects(): void
    {
        $count = 0;
        foreach ($this->profile()->classSubjects() as $classNumber => $subjects) {
            $classId = $this->classIdByNumber[$classNumber] ?? null;
            if (! $classId) {
                continue;
            }
            foreach ($subjects as $subjectName) {
                $subjectId = $this->subjectIdByName[$subjectName] ?? null;
                if (! $subjectId) {
                    $this->command?->warn("    ⚠ Subject '{$subjectName}' missing for Class {$classNumber}");
                    continue;
                }
                $teacherLabel = $this->profile()->subjectTeacherLabels()[$subjectName] ?? null;
                $teacherId = $teacherLabel ? ($this->staff->userIdByLabel[$teacherLabel] ?? null) : null;

                DB::table('class_subjects')->insert([
                    'id' => (string) Str::ulid(),
                    'class_id' => $classId,
                    'section_id' => null, // applies to all sections of this class
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacherId,
                    'academic_year_id' => $this->academicYearId,
                    'is_optional' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }
        $this->command?->line("  ✓ class_subjects seeded ({$count})");
    }
}
