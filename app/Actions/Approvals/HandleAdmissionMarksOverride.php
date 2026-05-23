<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\StudentApplication;
use App\Services\AdmissionScoringService;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\Log;

/**
 * Dual-approval flow for editing already-saved admission marks.
 *
 *   Stage 1 — institute_head approves the override.
 *     Handler chains a stage-2 ApprovalRequest at exam_admin.
 *   Stage 2 — exam_admin approves the override.
 *     Handler writes the new marks onto the application and reruns the
 *     scoring pipeline so weighted score + auto-decision update.
 *
 * Allowed fields are restricted to entry_test_score and interview_score
 * to make sure this path can't be abused for arbitrary writes.
 */
class HandleAdmissionMarksOverride
{
    private const ALLOWED_FIELDS = ['entry_test_score', 'interview_score'];

    public function handle(ApprovalRequest $approval): void
    {
        $payload = $approval->payload ?? [];
        $stage   = (int) ($payload['stage'] ?? 1);

        if ($stage === 1) {
            $this->advanceToStageTwo($approval, $payload);
            return;
        }

        $this->applyMarks($approval, $payload);
    }

    protected function advanceToStageTwo(ApprovalRequest $first, array $payload): void
    {
        $requester = $first->requested_by_id
            ? SchoolUser::find($first->requested_by_id)
            : SchoolUser::firstOrFail();

        $second = app(ApprovalService::class)->submit(
            requestedBy:    $requester,
            actionType:     'admission_marks_override',
            subject:        null,
            payload:        array_merge($payload, [
                'stage'             => 2,
                'parent_request_id' => $first->id,
            ]),
            approverLevel:  'exam_admin',
        );

        if ($first->requested_by_id) {
            InAppNotification::create([
                'user_id' => $first->requested_by_id,
                'title'   => 'Marks override · exam admin review needed',
                'body'    => 'Institute head approved the marks edit; exam admin approval is the second step.',
                'type'    => 'info',
            ]);
        }

        Log::info('admission_marks_override stage 1 → 2 chained', [
            'first_id'  => $first->id,
            'second_id' => $second->id,
        ]);
    }

    protected function applyMarks(ApprovalRequest $second, array $payload): void
    {
        $applicationId = $payload['application_id'] ?? null;
        $field         = $payload['field']          ?? null;
        $newValue      = $payload['new_value']      ?? null;
        $reason        = $payload['reason']         ?? null;

        if (! $applicationId || ! $field || $newValue === null) {
            Log::warning('admission_marks_override stage 2: missing payload', [
                'approval_id' => $second->id,
            ]);
            return;
        }

        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            Log::warning('admission_marks_override stage 2: disallowed field', [
                'approval_id' => $second->id,
                'field'       => $field,
            ]);
            return;
        }

        $app = StudentApplication::find($applicationId);
        if (! $app) {
            Log::warning('admission_marks_override stage 2: application missing', [
                'approval_id'    => $second->id,
                'application_id' => $applicationId,
            ]);
            return;
        }

        $oldValue = $app->{$field};

        $app->update([
            $field          => $newValue,
            'decision_notes' => trim(
                ($app->decision_notes ? $app->decision_notes . "\n" : '')
                . sprintf(
                    'Marks override approved (institute head + exam admin): %s changed from %s to %s%s',
                    $field,
                    $oldValue ?? '—',
                    $newValue,
                    $reason ? ' — ' . $reason : '',
                )
            ),
            'reviewed_at'    => now(),
        ]);

        // Rerun scoring + auto-decision so the weighted score reflects
        // the new component value. Auto-decision may flip status if the
        // edit pushes the applicant above/below thresholds.
        app(AdmissionScoringService::class)->evaluate($app->refresh());

        if ($second->requested_by_id) {
            InAppNotification::create([
                'user_id' => $second->requested_by_id,
                'title'   => 'Marks override applied',
                'body'    => sprintf(
                    'Override for %s on %s applied (%s → %s).',
                    $field,
                    $app->full_name,
                    $oldValue ?? '—',
                    $newValue,
                ),
                'type'    => 'success',
            ]);
        }
    }
}
