<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Concerns;

use App\Models\Tenant\Student;

/**
 * Resolves the Student row behind the signed-in school_user.
 *
 * Every page in the student portal reads through this so scoping is defined
 * in exactly one place. If a page ever queries a table by class_id or
 * section_id, it must take those from $this->student() and never from a
 * request parameter — otherwise one student could read another's records by
 * editing a URL.
 */
trait ResolvesCurrentStudent
{
    protected ?Student $resolvedStudent = null;

    protected function student(): ?Student
    {
        if ($this->resolvedStudent instanceof Student) {
            return $this->resolvedStudent;
        }

        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return null;
        }

        return $this->resolvedStudent = Student::query()
            ->with(['schoolClass', 'section', 'campus', 'academicYear', 'category'])
            ->where('school_user_id', $user->id)
            ->first();
    }

    /**
     * The student's own id, or a value that can never match a row. Used in
     * where() clauses so a missing student yields an empty result set rather
     * than an unscoped query returning the whole school.
     */
    protected function studentId(): string
    {
        return $this->student()?->id ?? '__no_student__';
    }

    protected function studentClassId(): ?string
    {
        return $this->student()?->class_id;
    }

    protected function studentSectionId(): ?string
    {
        return $this->student()?->section_id;
    }
}
