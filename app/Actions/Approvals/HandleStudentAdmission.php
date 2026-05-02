<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\StudentStatus;
use App\Models\ApprovalRequest;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\Student;
use App\Services\StudentAccountActivator;
use Illuminate\Support\Facades\Log;

/**
 * HandleStudentAdmission — runs when an institute head approves a
 * student-admission request. Transitions the Student row from
 * pending_admission to enrolled, generates a registration_number, and
 * dispatches the activation email.
 */
class HandleStudentAdmission
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload ?? [];
        $studentId = $payload['student_id'] ?? null;
        if (! $studentId) {
            return;
        }

        $student = Student::find($studentId);
        if (! $student) {
            Log::warning('HandleStudentAdmission: student missing', [
                'approval_id' => $approval->id,
                'student_id'  => $studentId,
            ]);
            return;
        }

        $this->admit($student);

        // Notify the requester.
        if ($approval->requested_by_id) {
            InAppNotification::create([
                'user_id' => $approval->requested_by_id,
                'title'   => 'Student admission approved',
                'body'    => "{$student->full_name} has been admitted. Registration No: {$student->registration_number}.",
                'type'    => 'success',
            ]);
        }
    }

    /**
     * Activate a student record (used by both the approval pipeline and
     * the bypass-approval path in admin's CreateStudent).
     */
    public function admit(Student $student, ?string $reason = null): Student
    {
        $student->forceFill([
            'status'               => StudentStatus::Enrolled->value,
            'registration_number'  => $student->registration_number ?: $this->generateRegistrationNumber($student),
            'status_changed_at'    => now(),
            'status_change_reason' => $reason ?: 'Approved by institute head',
        ])->saveQuietly();

        if ($student->email) {
            app(StudentAccountActivator::class)->activateStudent($student->refresh());
        }

        return $student->refresh();
    }

    protected function generateRegistrationNumber(Student $student): string
    {
        $year = (string) ($student->admission_date?->format('Y') ?? now()->year);
        $base = 'REG-' . $year . '-';

        $existing = Student::where('registration_number', 'like', $base . '%')
            ->orderByDesc('registration_number')
            ->value('registration_number');

        $next = 1;
        if ($existing && preg_match('/-(\d+)$/', $existing, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
