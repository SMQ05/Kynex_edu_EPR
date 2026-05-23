<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * f. AttendanceSeeder
 *
 * 3 months of daily attendance Mar-May 2026 (up to today's date).
 * Mon-Sat working days, Pakistan public holidays skipped. Per-student
 * status distribution: 85% present, 10% absent, 3% late, 2% leave —
 * with 5-8 chronic absentees skewed toward absent.
 *
 * Also seeds attendance_settings (single row per main campus) so the
 * settings page has data.
 */
class AttendanceSeeder extends Seeder
{
    public const HOLIDAYS = [
        '2026-03-23' => 'Pakistan Day',
        '2026-04-15' => 'In-school Sports Day (no class attendance)',
        '2026-05-01' => 'Labour Day',
    ];

    public const TODAY = '2026-05-05'; // pinned to plan date

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
        public StudentsAndParentsSeeder $studentsAndParents,
    ) {}

    public function run(): void
    {
        $academicYearId = $this->classes->academicYearId;
        $mainCampusId = (string) DB::table('campuses')
            ->where('is_main_campus', true)
            ->value('id');

        $this->seedAttendanceSettings($mainCampusId);
        $this->seedAttendance($academicYearId);
    }

    protected function seedAttendanceSettings(string $mainCampusId): void
    {
        // Per-section settings cleared and reseeded for the active sections.
        DB::table('attendance_settings')->delete();
        DB::table('attendance_settings')->insert([
            'id' => (string) Str::ulid(),
            'campus_id' => $mainCampusId,
            'class_id' => null,
            'section_id' => null,
            'school_start_time' => '07:30:00',
            'school_end_time' => '14:00:00',
            'late_arrival_cutoff' => '08:00:00',
            'grace_period_minutes' => 5,
            'notify_on_late_arrival' => true,
            'half_day_cutoff' => '11:00:00',
            'early_departure_cutoff' => '13:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command?->line('  ✓ attendance_settings seeded (1 default)');
    }

    protected function seedAttendance(string $academicYearId): void
    {
        DB::table('attendance_records')->delete();

        $start = Carbon::parse('2026-03-01');
        $today = Carbon::parse(self::TODAY);

        // Pick 6 chronic absentees (id => true) — these students get a
        // higher absent rate.
        $chronicCount = 6;
        $chronics = [];
        $studentRows = $this->studentsAndParents->studentRows;
        $idx = array_rand($studentRows, $chronicCount);
        foreach ((array) $idx as $i) {
            $chronics[$studentRows[$i]['id']] = true;
        }

        // Map class_number+section_id → class_teacher (marker).
        $teacherIdBySection = [];
        foreach ($this->classes->sectionByKey as $sec) {
            $teacherIdBySection[$sec['id']] = $sec['class_teacher_id'] ?? null;
        }

        $rowsBuf = [];
        $rowCount = 0;
        $batchSize = 500;
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($today)) {
            $iso = $cursor->toDateString();
            $isWorkingDay = $cursor->dayOfWeekIso !== Carbon::SUNDAY
                && ! isset(self::HOLIDAYS[$iso]);

            if ($isWorkingDay) {
                foreach ($studentRows as $s) {
                    $isChronic = isset($chronics[$s['id']]);
                    $r = mt_rand(1, 100);
                    if ($isChronic) {
                        // Chronic: 60% present, 30% absent, 7% late, 3% leave
                        $status = $r <= 60 ? 'present' : ($r <= 90 ? 'absent' : ($r <= 97 ? 'late' : 'leave'));
                    } else {
                        // Normal: 85% present, 10% absent, 3% late, 2% leave
                        $status = $r <= 85 ? 'present' : ($r <= 95 ? 'absent' : ($r <= 98 ? 'late' : 'leave'));
                    }

                    $remarks = match ($status) {
                        'leave' => collect(['Sickness', 'Family event', 'Medical appointment'])->random(),
                        'late' => null,
                        default => null,
                    };

                    $rowsBuf[] = [
                        'id' => (string) Str::ulid(),
                        'student_id' => $s['id'],
                        'class_id' => $s['class_id'],
                        'section_id' => $s['section_id'],
                        'date' => $iso,
                        'remarks' => $remarks,
                        'marked_by' => $teacherIdBySection[$s['section_id']] ?? null,
                        'academic_year_id' => $academicYearId,
                        'status' => $status,
                        'late_minutes' => $status === 'late' ? mt_rand(5, 30) : null,
                        'notified_at' => null,
                        'created_at' => $cursor->copy()->setTime(8, 30, 0),
                        'updated_at' => $cursor->copy()->setTime(8, 30, 0),
                    ];
                    $rowCount++;

                    if (count($rowsBuf) >= $batchSize) {
                        DB::table('attendance_records')->insert($rowsBuf);
                        $rowsBuf = [];
                    }
                }
            }

            $cursor->addDay();
        }

        if (! empty($rowsBuf)) {
            DB::table('attendance_records')->insert($rowsBuf);
        }

        $this->command?->line("  ✓ attendance_records seeded ({$rowCount} rows across Mar-May 2026)");
    }
}
