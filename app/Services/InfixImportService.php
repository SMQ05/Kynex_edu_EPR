<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\Department;
use App\Models\Tenant\Designation;
use App\Models\Tenant\FeeGroup;
use App\Models\Tenant\FeeType;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentCategory;
use App\Models\Tenant\StudentGuardian;
use App\Models\Tenant\Subject;
use App\Models\SchoolUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Imports data from InfixEdu MySQL database dump into KynexEdu schema.
 *
 * Expects a MySQL connection named 'infix_source' configured in database.php.
 * Reads from legacy tables and maps to current tenant tables.
 */
class InfixImportService
{
    protected string $connection = 'infix_source';

    protected array $log = [];
    protected array $stats = [
        'academic_years' => 0,
        'classes'        => 0,
        'sections'       => 0,
        'subjects'       => 0,
        'departments'    => 0,
        'designations'   => 0,
        'categories'     => 0,
        'fee_groups'     => 0,
        'fee_types'      => 0,
        'students'       => 0,
        'guardians'      => 0,
        'staff'          => 0,
        'skipped'        => 0,
        'errors'         => 0,
    ];

    protected ?string $defaultCampusId = null;
    protected ?string $defaultAcademicYearId = null;

    // Legacy ID → new ULID maps
    protected array $classMap = [];
    protected array $sectionMap = [];
    protected array $subjectMap = [];
    protected array $categoryMap = [];
    protected array $departmentMap = [];
    protected array $designationMap = [];
    protected array $feeGroupMap = [];
    protected array $feeTypeMap = [];
    protected array $academicYearMap = [];
    protected array $studentMap = [];
    protected array $userMap = [];

    /**
     * Run the full import pipeline.
     */
    public function import(callable $progressCallback = null): array
    {
        $this->ensureDefaults();

        $steps = [
            'importAcademicYears',
            'importDepartments',
            'importDesignations',
            'importCategories',
            'importClasses',
            'importSections',
            'importSubjects',
            'importFeeGroups',
            'importFeeTypes',
            'importStaff',
            'importStudents',
        ];

        foreach ($steps as $index => $step) {
            try {
                $this->addLog("Starting: {$step}");
                $this->{$step}();
                $this->addLog("Completed: {$step}");
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                $this->addLog("ERROR in {$step}: {$e->getMessage()}");
                Log::error("InfixImport::{$step} failed", ['error' => $e->getMessage()]);
            }

            if ($progressCallback) {
                $progressCallback($index + 1, count($steps), $step);
            }
        }

        return [
            'stats' => $this->stats,
            'log'   => $this->log,
        ];
    }

    /**
     * Ensure default campus and academic year exist.
     */
    protected function ensureDefaults(): void
    {
        $campus = Campus::first();
        if (! $campus) {
            $campus = Campus::create([
                'name'      => 'Main Campus',
                'code'      => 'MAIN',
                'is_active' => true,
            ]);
        }
        $this->defaultCampusId = $campus->id;

        $ay = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        if ($ay) {
            $this->defaultAcademicYearId = $ay->id;
        }
    }

    // ── Academic Years ──────────────────────────────────────────

    protected function importAcademicYears(): void
    {
        $rows = $this->query('sm_academic_years');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = AcademicYear::where('title', $row->year ?? $row->title ?? '')->first();
            if ($existing) {
                $this->academicYearMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $ay = AcademicYear::create([
                'title'      => $row->year ?? $row->title ?? "Year {$row->id}",
                'start_date' => $row->start_date ?? now()->startOfYear(),
                'end_date'   => $row->end_date ?? now()->endOfYear(),
                'is_active'  => (bool) ($row->is_running ?? false),
            ]);

            $this->academicYearMap[$row->id] = $ay->id;
            $this->stats['academic_years']++;

            if (! $this->defaultAcademicYearId && ($row->is_running ?? false)) {
                $this->defaultAcademicYearId = $ay->id;
            }
        }
    }

    // ── Departments ─────────────────────────────────────────────

    protected function importDepartments(): void
    {
        $rows = $this->query('sm_human_departments');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = Department::where('name', $row->name)->first();
            if ($existing) {
                $this->departmentMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $dept = Department::create([
                'name' => $row->name,
            ]);

            $this->departmentMap[$row->id] = $dept->id;
            $this->stats['departments']++;
        }
    }

    // ── Designations ────────────────────────────────────────────

    protected function importDesignations(): void
    {
        $rows = $this->query('sm_designations');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = Designation::where('name', $row->title)->first();
            if ($existing) {
                $this->designationMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $des = Designation::create([
                'name' => $row->title,
            ]);

            $this->designationMap[$row->id] = $des->id;
            $this->stats['designations']++;
        }
    }

    // ── Student Categories ──────────────────────────────────────

    protected function importCategories(): void
    {
        $rows = $this->query('sm_student_categories');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = StudentCategory::where('name', $row->category_name)->first();
            if ($existing) {
                $this->categoryMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $cat = StudentCategory::create([
                'name' => $row->category_name,
            ]);

            $this->categoryMap[$row->id] = $cat->id;
            $this->stats['categories']++;
        }
    }

    // ── Classes ─────────────────────────────────────────────────

    protected function importClasses(): void
    {
        $rows = $this->query('sm_classes');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = SchoolClass::where('name', $row->class_name)->first();
            if ($existing) {
                $this->classMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $class = SchoolClass::create([
                'name'       => $row->class_name,
                'short_name' => Str::limit($row->class_name, 10, ''),
                'sort_order' => $row->id,
                'campus_id'  => $this->defaultCampusId,
            ]);

            $this->classMap[$row->id] = $class->id;
            $this->stats['classes']++;
        }
    }

    // ── Sections ────────────────────────────────────────────────

    protected function importSections(): void
    {
        $rows = $this->query('sm_sections');
        if (! $rows) return;

        foreach ($rows as $row) {
            $classId = $this->classMap[$row->class_id ?? 0] ?? null;

            $existing = Section::where('name', $row->section_name)
                ->when($classId, fn ($q) => $q->where('class_id', $classId))
                ->first();

            if ($existing) {
                $this->sectionMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $section = Section::create([
                'name'     => $row->section_name,
                'class_id' => $classId,
                'capacity' => $row->capacity ?? 40,
            ]);

            $this->sectionMap[$row->id] = $section->id;
            $this->stats['sections']++;
        }
    }

    // ── Subjects ────────────────────────────────────────────────

    protected function importSubjects(): void
    {
        $rows = $this->query('sm_subjects');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = Subject::where('name', $row->subject_name)->first();
            if ($existing) {
                $this->subjectMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $subject = Subject::create([
                'name'       => $row->subject_name,
                'code'       => $row->subject_code ?? Str::upper(Str::limit($row->subject_name, 5, '')),
                'subject_type' => $row->subject_type ?? 'theory',
            ]);

            $this->subjectMap[$row->id] = $subject->id;
            $this->stats['subjects']++;
        }
    }

    // ── Fee Groups ──────────────────────────────────────────────

    protected function importFeeGroups(): void
    {
        $rows = $this->query('sm_fees_groups');
        if (! $rows) return;

        foreach ($rows as $row) {
            $existing = FeeGroup::where('name', $row->name)->first();
            if ($existing) {
                $this->feeGroupMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $fg = FeeGroup::create([
                'name'        => $row->name,
                'description' => $row->description ?? null,
            ]);

            $this->feeGroupMap[$row->id] = $fg->id;
            $this->stats['fee_groups']++;
        }
    }

    // ── Fee Types ───────────────────────────────────────────────

    protected function importFeeTypes(): void
    {
        $rows = $this->query('sm_fees_types');
        if (! $rows) return;

        foreach ($rows as $row) {
            $groupId = $this->feeGroupMap[$row->fees_group_id ?? 0] ?? null;

            $existing = FeeType::where('name', $row->name)
                ->when($groupId, fn ($q) => $q->where('fee_group_id', $groupId))
                ->first();

            if ($existing) {
                $this->feeTypeMap[$row->id] = $existing->id;
                $this->stats['skipped']++;
                continue;
            }

            $ft = FeeType::create([
                'name'         => $row->name,
                'fee_group_id' => $groupId,
                'description'  => $row->description ?? null,
            ]);

            $this->feeTypeMap[$row->id] = $ft->id;
            $this->stats['fee_types']++;
        }
    }

    // ── Staff ───────────────────────────────────────────────────

    protected function importStaff(): void
    {
        $rows = $this->query('sm_staffs');
        if (! $rows) return;

        foreach ($rows as $row) {
            try {
                $email = $row->email ?? "staff_{$row->id}@imported.local";

                $existingUser = SchoolUser::where('email', $email)->first();
                if ($existingUser) {
                    $this->userMap[$row->id] = $existingUser->id;
                    $this->stats['skipped']++;
                    continue;
                }

                $user = SchoolUser::create([
                    'name'      => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                    'email'     => $email,
                    'password'  => Hash::make('changeme123'),
                    'phone'     => $row->mobile ?? $row->phone ?? null,
                    'campus_id' => $this->defaultCampusId,
                ]);

                $this->userMap[$row->id] = $user->id;

                StaffProfile::create([
                    'school_user_id'  => $user->id,
                    'employee_id'     => $row->staff_no ?? "IMP-{$row->id}",
                    'department_id'   => $this->departmentMap[$row->department_id ?? 0] ?? null,
                    'designation_id'  => $this->designationMap[$row->designation_id ?? 0] ?? null,
                    'date_of_joining' => $row->date_of_joining ?? null,
                    'date_of_birth'   => $row->date_of_birth ?? null,
                    'gender'          => $row->gender ?? null,
                    'qualification'   => $row->qualification ?? null,
                    'employment_type' => 'permanent',
                ]);

                $this->stats['staff']++;
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                $this->addLog("Staff import error (ID:{$row->id}): {$e->getMessage()}");
            }
        }
    }

    // ── Students ────────────────────────────────────────────────

    protected function importStudents(): void
    {
        $rows = $this->query('sm_students');
        if (! $rows) return;

        foreach ($rows as $row) {
            try {
                $admNo = $row->admission_no ?? "IMP-{$row->id}";

                $existing = Student::where('admission_number', $admNo)->first();
                if ($existing) {
                    $this->studentMap[$row->id] = $existing->id;
                    $this->stats['skipped']++;
                    continue;
                }

                // Create school_user for the student
                $email = $row->email ?? "student_{$row->id}@imported.local";
                $user = SchoolUser::where('email', $email)->first();
                if (! $user) {
                    $user = SchoolUser::create([
                        'name'      => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                        'email'     => $email,
                        'password'  => Hash::make('changeme123'),
                        'phone'     => $row->mobile ?? null,
                        'campus_id' => $this->defaultCampusId,
                    ]);
                }

                $student = Student::create([
                    'school_user_id'   => $user->id,
                    'admission_number' => $admNo,
                    'first_name'       => $row->first_name ?? 'Unknown',
                    'last_name'        => $row->last_name ?? '',
                    'date_of_birth'    => $row->date_of_birth ?? null,
                    'gender'           => $this->mapGender($row->gender ?? null),
                    'blood_group'      => $row->blood_group ?? null,
                    'religion'         => $row->religion ?? null,
                    'nationality'      => $row->nationality ?? 'Pakistani',
                    'academic_year_id' => $this->academicYearMap[$row->session_id ?? 0] ?? $this->defaultAcademicYearId,
                    'class_id'         => $this->classMap[$row->class_id ?? 0] ?? null,
                    'section_id'       => $this->sectionMap[$row->section_id ?? 0] ?? null,
                    'category_id'      => $this->categoryMap[$row->category_id ?? 0] ?? null,
                    'campus_id'        => $this->defaultCampusId,
                    'admission_date'   => $row->admission_date ?? $row->created_at ?? now(),
                    'status'           => ($row->active_status ?? 1) == 1 ? 'enrolled' : 'inactive',
                    'current_address'  => $row->current_address ?? null,
                    'permanent_address' => $row->permanent_address ?? null,
                ]);

                $this->studentMap[$row->id] = $student->id;

                // Import guardian if available
                $this->importStudentGuardian($row, $student);

                $this->stats['students']++;
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                $this->addLog("Student import error (ID:{$row->id}): {$e->getMessage()}");
            }
        }
    }

    protected function importStudentGuardian(object $row, Student $student): void
    {
        $guardianName = $row->father_name ?? $row->guardian_name ?? null;
        if (! $guardianName) return;

        try {
            StudentGuardian::create([
                'student_id'   => $student->id,
                'name'         => $guardianName,
                'relation'     => $row->relation ?? 'father',
                'phone'        => $row->guardian_mobile ?? $row->guardian_phone ?? null,
                'email'        => $row->guardian_email ?? null,
                'occupation'   => $row->guardian_occupation ?? null,
                'address'      => $row->guardian_address ?? null,
                'is_primary'   => true,
            ]);
            $this->stats['guardians']++;
        } catch (\Throwable $e) {
            $this->addLog("Guardian import error (Student:{$student->id}): {$e->getMessage()}");
        }
    }

    // ── Helpers ──────────────────────────────────────────────────

    protected function query(string $table): ?array
    {
        try {
            $results = DB::connection($this->connection)
                ->table($table)
                ->get()
                ->toArray();

            $this->addLog("Read " . count($results) . " rows from {$table}");
            return $results;
        } catch (\Throwable $e) {
            $this->addLog("Could not read table '{$table}': {$e->getMessage()}");
            return null;
        }
    }

    protected function mapGender(?string $gender): ?string
    {
        if (! $gender) return null;

        return match (strtolower($gender)) {
            'male', 'm'   => 'male',
            'female', 'f' => 'female',
            default        => 'other',
        };
    }

    protected function addLog(string $message): void
    {
        $this->log[] = '[' . now()->format('H:i:s') . '] ' . $message;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getLog(): array
    {
        return $this->log;
    }
}
