<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Approvals\HandleStudentAdmission;
use App\Enums\ApplicationStatus;
use App\Enums\StudentStatus;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentApplication;
use Illuminate\Support\Str;
use RuntimeException;

class StudentApplicationService
{
    /**
     * Convert an approved application into a Student row, then run the
     * HandleStudentAdmission flow (which sets status=enrolled, generates
     * registration_number, and emails activation).
     */
    public function admit(StudentApplication $app): Student
    {
        if ($app->student_id) {
            $student = Student::find($app->student_id);
            if ($student) {
                return $student;
            }
        }

        // Required-field defaults: fall back to current academic year and
        // first available class/section if the application didn't specify
        // one. Throw a clear error if neither the application nor the
        // tenant has a class set up yet.
        $academicYearId = $app->academic_year_id
            ?: AcademicYear::where('is_current', true)->value('id')
            ?: AcademicYear::orderByDesc('start_date')->value('id');

        $classId = $app->class_id
            ?: SchoolClass::orderBy('sort_order')->orderBy('name')->value('id');

        $sectionId = $app->section_id ?? null;
        if (! $sectionId && $classId) {
            $sectionId = Section::where('class_id', $classId)->orderBy('name')->value('id');
        }

        if (! $academicYearId) {
            throw new RuntimeException('Cannot admit: no academic year configured. Create one in Academic Setup → Academic Years first.');
        }
        if (! $classId) {
            throw new RuntimeException('Cannot admit: no classes configured. Create at least one Class first.');
        }
        if (! $sectionId) {
            throw new RuntimeException('Cannot admit: class has no sections. Create at least one Section under the chosen class.');
        }

        $student = Student::create([
            'admission_number' => $this->generateAdmissionNumber(),
            'admission_date'   => now()->toDateString(),
            'academic_year_id' => $academicYearId,
            'class_id'         => $classId,
            'section_id'       => $sectionId,
            'campus_id'        => $app->campus_id,
            'first_name'       => $app->first_name,
            'last_name'        => $app->last_name,
            'date_of_birth'    => $app->date_of_birth,
            'gender'           => $app->gender ?? 'male',
            'phone'            => $app->phone,
            'email'            => $app->email,
            'address'          => $app->address,
            'city'             => $app->city,
            'previous_school'  => $app->previous_school,
            'nationality'      => 'Pakistani',
            'status'           => StudentStatus::PendingAdmission->value,
        ]);

        // Auto-create a primary guardian if any guardian info was supplied.
        if (filled($app->father_name) || filled($app->mother_name) || filled($app->guardian_phone) || filled($app->guardian_email)) {
            $guardianType = $app->father_name
                ? \App\Enums\GuardianType::Father->value
                : ($app->mother_name ? \App\Enums\GuardianType::Mother->value : \App\Enums\GuardianType::Guardian->value);

            $student->guardians()->create([
                'name'                => $app->father_name ?: $app->mother_name ?: 'Guardian',
                'relationship'        => $app->father_name ? 'Father' : ($app->mother_name ? 'Mother' : 'Guardian'),
                'guardian_type'       => $guardianType,
                // student_guardians.phone is NOT NULL in the schema; fall back
                // to a placeholder so the row inserts. Admins can edit later.
                'phone'               => $app->guardian_phone ?: '0000000000',
                'email'               => $app->guardian_email,
                'is_primary_contact'  => true,
                'can_access_portal'   => filled($app->guardian_email),
            ]);
        }

        // Run the full admission pipeline (issues registration_number,
        // sends activation email).
        $student = app(HandleStudentAdmission::class)->admit(
            $student,
            'Admitted from application ' . $app->id,
        );

        $app->update([
            'status'      => ApplicationStatus::Admitted,
            'student_id'  => $student->id,
            'reviewed_at' => now(),
        ]);

        return $student;
    }

    public function generateAdmissionNumber(): string
    {
        $year = now()->format('Y');
        $base = 'ADM-' . $year . '-';

        $existing = Student::where('admission_number', 'like', $base . '%')
            ->orderByDesc('admission_number')
            ->value('admission_number');

        $next = 1;
        if ($existing && preg_match('/-(\d+)$/', $existing, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
