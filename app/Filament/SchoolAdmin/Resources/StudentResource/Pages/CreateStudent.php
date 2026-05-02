<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentResource\Pages;

use App\Enums\StudentStatus;
use App\Filament\SchoolAdmin\Resources\StudentResource;
use App\Services\ApprovalService;
use App\Services\StudentAccountActivator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * Admin-side student creation always lands the record in
 * pending_admission state and submits a student_admission approval
 * request. The institute head reviews it from the Approval Queue page.
 * Privileged users (bypass_approvals) auto-approve and skip the queue.
 */
class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->guard('school_users')->user();
        $bypass = static::userBypassesApproval($user);

        // Force-bind to the actor's own campus when they're scoped to one.
        // Prevents the form from being POSTed with a different campus_id
        // even if the disabled select is bypassed client-side.
        $scoped = \App\Filament\SchoolAdmin\Resources\StudentResource::scopedCampusId();
        if ($scoped) {
            $data['campus_id'] = $scoped;
        }

        if (! $bypass) {
            $data['status'] = StudentStatus::PendingAdmission->value;
            // registration_number is intentionally NOT set here; it is
            // assigned by HandleStudentAdmission once approved.
            $data['registration_number'] = null;
        }

        return $data;
    }

    /**
     * Per spec: only INSTITUTE_HEAD and MULTI_INSTITUTE_HEAD acting in
     * that capacity may admit students directly. Everyone else routes
     * through the approval queue. We check the *active role* so the same
     * user can run as SCHOOL_ADMIN (queue) and switch to INSTITUTE_HEAD
     * (direct) on different sessions.
     */
    protected static function userBypassesApproval($user): bool
    {
        if (! $user) {
            return false;
        }
        $activeRole = $user->active_role ?? $user->roles->first()?->name;
        return in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);
    }

    protected function afterCreate(): void
    {
        $student = $this->record;
        $user = auth()->guard('school_users')->user();
        $bypass = static::userBypassesApproval($user);

        // Privileged users skip the approval queue: mark enrolled, generate
        // registration_number, send activation email immediately.
        if ($bypass) {
            $admitted = app(\App\Actions\Approvals\HandleStudentAdmission::class)
                ->admit($student, "Admitted directly by {$user?->name} (bypass_approvals)");

            Notification::make()
                ->title('Student admitted')
                ->body("Registration No: {$admitted->registration_number}. Activation email queued.")
                ->success()
                ->send();
            return;
        }

        // Otherwise route through approval.
        app(ApprovalService::class)->submit(
            requestedBy: $user,
            actionType: 'student_admission',
            subject: $student,
            payload: [
                'student_id'       => $student->id,
                'student_name'     => $student->full_name,
                'admission_number' => $student->admission_number,
                'class_id'         => $student->class_id,
                'section_id'       => $student->section_id,
                'submitted_by'     => $user?->name,
                'reason'           => 'Admin-initiated admission',
            ],
        );

        Notification::make()
            ->title('Sent for institute-head approval')
            ->body("Student record is in pending_admission. The institute head can approve from the Approval Queue.")
            ->warning()
            ->send();
    }

}
