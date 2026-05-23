<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\ApplicationStatus;
use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\StudentApplication;
use App\Services\ApprovalService;
use App\Services\StudentApplicationService;
use Illuminate\Support\Facades\Log;

/**
 * Handler for the manual-override path on auto-decided admissions.
 *
 * Stage 1 — `approver_level=institute_head`. Approving creates a
 *   second ApprovalRequest with `approver_level=exam_admin`.
 * Stage 2 — `approver_level=exam_admin`. Approving applies the
 *   override to the StudentApplication (status flip, optional admit).
 *
 * Both stages share the same `action_type` (`admission_decision_override`)
 * and the same handler — the stage is driven by `payload.stage`.
 */
class HandleAdmissionDecisionOverride
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload ?? [];
        $stage   = (int) ($payload['stage'] ?? 1);

        if ($stage === 1) {
            $this->advanceToStageTwo($approval, $payload);
            return;
        }

        $this->applyOverride($approval, $payload);
    }

    protected function advanceToStageTwo(ApprovalRequest $first, array $payload): void
    {
        // Carry the same payload forward but tag stage = 2 and pin the
        // approver to exam_admin. Subject + requester are the same as
        // stage 1 so the queue shows continuity.
        $requester = $this->resolveRequester($first);

        $second = app(ApprovalService::class)->submit(
            requestedBy:    $requester,
            actionType:     'admission_decision_override',
            subject:        null,
            payload:        array_merge($payload, ['stage' => 2, 'parent_request_id' => $first->id]),
            approverLevel:  'exam_admin',
        );

        // Tell the requester that one half is done and exam admin needs to act.
        if ($first->requested_by_id) {
            InAppNotification::create([
                'user_id' => $first->requested_by_id,
                'title'   => 'Admission override · exam admin review needed',
                'body'    => 'Institute head approved your override; exam admin approval is the second step.',
                'type'    => 'info',
            ]);
        }

        Log::info('admission_decision_override stage 1 → 2 chained', [
            'first_id'  => $first->id,
            'second_id' => $second->id,
        ]);
    }

    protected function applyOverride(ApprovalRequest $second, array $payload): void
    {
        $applicationId = $payload['application_id'] ?? null;
        $newStatus     = $payload['new_status']     ?? null;
        $notes         = $payload['notes']          ?? null;

        if (! $applicationId || ! $newStatus) {
            Log::warning('admission_decision_override stage 2: missing payload', ['approval_id' => $second->id]);
            return;
        }

        $app = StudentApplication::find($applicationId);
        if (! $app) {
            Log::warning('admission_decision_override stage 2: application missing', [
                'approval_id'    => $second->id,
                'application_id' => $applicationId,
            ]);
            return;
        }

        // Special case: if the override target is "admitted", run the
        // full admission service (creates Student row, sends invite).
        if ($newStatus === ApplicationStatus::Admitted->value) {
            try {
                app(StudentApplicationService::class)->admit($app);
            } catch (\Throwable $e) {
                Log::warning('Override admit failed', [
                    'approval_id' => $second->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        } else {
            $app->update([
                'status'          => $newStatus,
                'decision_notes'  => trim(
                    ($app->decision_notes ? $app->decision_notes . "\n" : '')
                    . 'Manual override approved (institute head + exam admin)'
                    . ($notes ? ': ' . $notes : '')
                ),
                'reviewed_at'     => now(),
                'auto_rejected'   => false, // override clears the auto-reject flag
            ]);
        }

        if ($second->requested_by_id) {
            InAppNotification::create([
                'user_id' => $second->requested_by_id,
                'title'   => 'Admission override applied',
                'body'    => "Override for {$app->full_name} approved by both reviewers and applied.",
                'type'    => 'success',
            ]);
        }
    }

    protected function resolveRequester(ApprovalRequest $approval): SchoolUser
    {
        $user = $approval->requested_by_id
            ? SchoolUser::find($approval->requested_by_id)
            : null;

        return $user ?? SchoolUser::firstOrFail();
    }
}
