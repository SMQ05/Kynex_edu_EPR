<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentApplication;
use Illuminate\Support\Facades\Log;

class RunnerUpService
{
    public function __construct(private readonly StudentApplicationService $admissionService) {}

    /**
     * When an admitted application is cancelled/rejected, find the
     * highest-scoring waitlisted applicant for the same class and academic year
     * and promote them to admitted.
     */
    public function promote(StudentApplication $cancelledApp): ?Student
    {
        if (! $cancelledApp->class_id || ! $cancelledApp->academic_year_id) {
            return null;
        }

        $next = StudentApplication::where('status', ApplicationStatus::Waitlisted->value)
            ->where('class_id', $cancelledApp->class_id)
            ->where('academic_year_id', $cancelledApp->academic_year_id)
            ->whereNull('student_id')
            ->orderByDesc('final_percentage')
            ->orderBy('created_at')
            ->first();

        if (! $next) {
            Log::info('RunnerUpService: no waitlisted applicant found', [
                'class_id'         => $cancelledApp->class_id,
                'academic_year_id' => $cancelledApp->academic_year_id,
            ]);
            return null;
        }

        try {
            $student = $this->admissionService->admitRunnerUp($next);
            Log::info('RunnerUpService: promoted waitlisted applicant', [
                'application_id' => $next->id,
                'student_id'     => $student->id,
            ]);
            return $student;
        } catch (\Throwable $e) {
            Log::warning('RunnerUpService: admission of runner-up failed', [
                'application_id' => $next->id,
                'error'          => $e->getMessage(),
            ]);
            return null;
        }
    }
}
