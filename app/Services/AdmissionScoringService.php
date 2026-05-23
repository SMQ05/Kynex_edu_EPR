<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Tenant\AdmissionCriteria;
use App\Models\Tenant\StudentApplication;

/**
 * Centralised scoring + decision logic for the admission lifecycle.
 *
 * Reads the active AdmissionCriteria for the application's
 * (academic_year, class), normalises each component to a percentage,
 * applies the configured weightages, and writes back the snapshot.
 *
 * Auto-reject behaviour is gated on the criteria's auto_reject_below_*
 * flags — when on, falling under min_test_score / min_interview_score
 * flips the application to Rejected with a populated reason.
 */
class AdmissionScoringService
{
    /**
     * Recompute final_score / final_percentage / weighted_components for
     * the application and persist them. Returns the application after
     * any auto-reject side effect.
     */
    public function recompute(StudentApplication $app): StudentApplication
    {
        $criteria = AdmissionCriteria::resolveFor($app->academic_year_id, $app->class_id);

        if (! $criteria) {
            // No criteria configured — leave snapshot empty so the UI
            // can show "set up admission criteria first".
            $app->forceFill([
                'final_score'         => null,
                'final_percentage'    => null,
                'weighted_components' => null,
            ])->save();
            return $app;
        }

        $components = [];
        $weightedSum = 0.0;
        $totalWeightApplied = 0;
        $skipInterview = (bool) ($criteria->skip_interview_for_all ?? false);

        // Test component — clamp per-component % at 100 so bonus-mark
        // edge cases (score > full marks) don't push the weighted
        // contribution above its assigned weightage.
        if ($criteria->test_weightage > 0 && $app->entry_test_score !== null) {
            $testFull = max(1, (int) $criteria->test_full_marks);
            $pct = min(100.0, ((float) $app->entry_test_score / $testFull) * 100);
            $weighted = ($pct * $criteria->test_weightage) / 100;
            $components['test'] = [
                'score'     => (float) $app->entry_test_score,
                'full'      => $testFull,
                'percent'   => round($pct, 2),
                'weight'    => $criteria->test_weightage,
                'weighted'  => round($weighted, 2),
            ];
            $weightedSum += $weighted;
            $totalWeightApplied += $criteria->test_weightage;
        }

        // Interview component — skipped entirely if criteria.skip_interview_for_all
        // is on. The test+previous weights then normalise to fill 100%.
        if (! $skipInterview
            && $criteria->interview_weightage > 0
            && $app->interview_score !== null
        ) {
            $iFull = max(1, (int) $criteria->interview_full_marks);
            $pct = min(100.0, ((float) $app->interview_score / $iFull) * 100);
            $weighted = ($pct * $criteria->interview_weightage) / 100;
            $components['interview'] = [
                'score'     => (float) $app->interview_score,
                'full'      => $iFull,
                'percent'   => round($pct, 2),
                'weight'    => $criteria->interview_weightage,
                'weighted'  => round($weighted, 2),
            ];
            $weightedSum += $weighted;
            $totalWeightApplied += $criteria->interview_weightage;
        }

        // Previous school marks
        if ($criteria->previous_score_weightage > 0
            && $app->previous_school_score !== null
            && $app->previous_score_max !== null
            && (float) $app->previous_score_max > 0
        ) {
            $pct = min(100.0, ((float) $app->previous_school_score / (float) $app->previous_score_max) * 100);
            $weighted = ($pct * $criteria->previous_score_weightage) / 100;
            $components['previous'] = [
                'score'    => (float) $app->previous_school_score,
                'full'     => (float) $app->previous_score_max,
                'percent'  => round($pct, 2),
                'weight'   => $criteria->previous_score_weightage,
                'weighted' => round($weighted, 2),
            ];
            $weightedSum += $weighted;
            $totalWeightApplied += $criteria->previous_score_weightage;
        }

        // Final percentage normalised by the actually-applied weights so
        // a partial-component snapshot still maps to 0..100. (When all
        // configured components are recorded and weightages sum to 100,
        // this is identical to weightedSum.)
        $finalPercentage = $totalWeightApplied > 0
            ? round(($weightedSum / $totalWeightApplied) * 100, 2)
            : null;

        $app->forceFill([
            'final_score'         => $finalPercentage !== null ? round($weightedSum, 2) : null,
            'final_percentage'    => $finalPercentage,
            'weighted_components' => $components,
        ])->save();

        return $app;
    }

    /**
     * After scores are recorded, check minima against the resolved
     * criteria. Auto-rejects (status flip + reason) when a flagged
     * threshold is breached. Returns true if the application was
     * auto-rejected.
     */
    public function applyAutoRejectIfBelowMinima(StudentApplication $app): bool
    {
        $criteria = AdmissionCriteria::resolveFor($app->academic_year_id, $app->class_id);
        if (! $criteria) {
            return false;
        }

        $reasons = [];

        if ($criteria->auto_reject_below_test
            && $criteria->min_test_score !== null
            && $app->entry_test_score !== null
            && (float) $app->entry_test_score < (float) $criteria->min_test_score
        ) {
            $reasons[] = sprintf(
                'Entry test score %.2f below minimum %.2f',
                (float) $app->entry_test_score,
                (float) $criteria->min_test_score,
            );
        }

        if (! ($criteria->skip_interview_for_all ?? false)
            && $criteria->auto_reject_below_interview
            && $criteria->min_interview_score !== null
            && $app->interview_score !== null
            && (float) $app->interview_score < (float) $criteria->min_interview_score
        ) {
            $reasons[] = sprintf(
                'Interview score %.2f below minimum %.2f',
                (float) $app->interview_score,
                (float) $criteria->min_interview_score,
            );
        }

        if ($criteria->min_final_percentage !== null
            && $app->final_percentage !== null
            && (float) $app->final_percentage < (float) $criteria->min_final_percentage
        ) {
            $reasons[] = sprintf(
                'Final percentage %.2f%% below minimum %.2f%%',
                (float) $app->final_percentage,
                (float) $criteria->min_final_percentage,
            );
        }

        if ($reasons === []) {
            return false;
        }

        $app->forceFill([
            'status'               => ApplicationStatus::Rejected,
            'auto_rejected'        => true,
            'auto_reject_reason'   => implode('; ', $reasons),
            'auto_decision_status' => ApplicationStatus::Rejected->value,
            'auto_decision_at'     => now(),
            'decision_notes'       => trim(
                ($app->decision_notes ? $app->decision_notes . "\n" : '')
                . 'Auto-rejected: ' . implode('; ', $reasons)
            ),
            'reviewed_at'          => now(),
        ])->save();

        return true;
    }

    /**
     * Once both test and interview scores are recorded and the applicant
     * is not auto-rejected, advance the application automatically:
     *
     *   - If the criteria has min_final_percentage and the applicant
     *     beats it (or no minimum is set), status becomes
     *     PendingApproval — the institute head's queue picks it up.
     *
     * The chosen status is mirrored into auto_decision_status so the
     * UI can detect later manual overrides and route them through the
     * dual-approval flow.
     *
     * Returns true if the auto-decision changed the status.
     */
    public function applyAutoDecisionIfReady(StudentApplication $app): bool
    {
        // Auto-rejected already counts as the auto-decision; nothing more
        // to do.
        if ($app->auto_rejected) {
            return false;
        }

        $criteria = AdmissionCriteria::resolveFor($app->academic_year_id, $app->class_id);
        if (! $criteria) {
            return false;
        }

        // Hold-pending semantics: only auto-decide once *every* component
        // that the criteria depends on has a recorded score. Components
        // with weightage = 0 are ignored. If skip_interview_for_all is on,
        // the interview component is excluded regardless of its weightage.
        $skipInterview = (bool) ($criteria->skip_interview_for_all ?? false);

        if ($criteria->test_weightage > 0 && $app->entry_test_score === null) {
            return false; // hold — test not recorded
        }
        if (! $skipInterview
            && $criteria->interview_weightage > 0
            && $app->interview_score === null
        ) {
            return false; // hold — interview not recorded
        }
        if ($criteria->previous_score_weightage > 0
            && ($app->previous_school_score === null || $app->previous_score_max === null)
        ) {
            return false; // hold — previous-school marks not on file
        }

        // If a final-percentage minimum is configured, require we beat it.
        if ($criteria->min_final_percentage !== null
            && $app->final_percentage !== null
            && (float) $app->final_percentage < (float) $criteria->min_final_percentage
        ) {
            return false; // applyAutoRejectIfBelowMinima will have handled this
        }

        $current = $app->status instanceof ApplicationStatus
            ? $app->status->value
            : (string) $app->status;

        // Don't move admitted/rejected/pending records again.
        if (in_array($current, [
            ApplicationStatus::Admitted->value,
            ApplicationStatus::Rejected->value,
            ApplicationStatus::PendingApproval->value,
        ], true)) {
            // But still record the auto-decision marker if not yet set.
            if (! $app->auto_decision_status) {
                $app->forceFill([
                    'auto_decision_status' => $current,
                    'auto_decision_at'     => now(),
                ])->save();
            }
            return false;
        }

        $app->forceFill([
            'status'               => ApplicationStatus::PendingApproval,
            'auto_decision_status' => ApplicationStatus::PendingApproval->value,
            'auto_decision_at'     => now(),
            'decision_notes'       => trim(
                ($app->decision_notes ? $app->decision_notes . "\n" : '')
                . sprintf(
                    'Auto-promoted to pending approval (final %.2f%%).',
                    (float) ($app->final_percentage ?? 0),
                )
            ),
        ])->save();

        return true;
    }

    /**
     * One-shot helper called from the UI after marks are recorded.
     * Recomputes weighted score, applies auto-reject, then auto-decision.
     */
    public function evaluate(StudentApplication $app): StudentApplication
    {
        $app = $this->recompute($app);
        $rejected = $this->applyAutoRejectIfBelowMinima($app->refresh());
        if (! $rejected) {
            $this->applyAutoDecisionIfReady($app->refresh());
        }
        return $app->refresh();
    }
}
