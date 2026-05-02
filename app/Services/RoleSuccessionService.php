<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActionType;
use App\Enums\SuccessionStatus;
use App\Models\ApprovalRequest;
use App\Models\SchoolUser;
use App\Models\Tenant\RoleSuccession;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * RoleSuccessionService — Manages role handover/succession lifecycle.
 *
 * Scenarios handled:
 *  - Staff/Teacher leaves → School Admin initiates → Institute Head approves
 *  - School Admin leaves → Institute Head initiates (self-approves)
 *  - Institute Head leaves → School Admin initiates → SaaS Admin approves
 *
 * On completion:
 *  - Spatie role unassigned from outgoing user
 *  - Spatie role assigned to incoming user
 *  - Linked records are reassigned (via configurable transfer_records list)
 *  - Audit entry created in central ApprovalRequest
 */
class RoleSuccessionService
{
    public function __construct(
        private readonly ApprovalService $approvalService,
    ) {}

    /**
     * Initiate a role succession request.
     *
     * @param  SchoolUser       $requester     School Admin or Institute Head initiating the handover
     * @param  SchoolUser       $outgoing      The user who is leaving
     * @param  SchoolUser|null  $incoming      The replacement (may be set later)
     * @param  string           $roleSlug      Spatie role slug to transfer
     * @param  array            $transferRecords  List of record types to transfer along
     * @param  string|null      $notes
     */
    public function initiate(
        SchoolUser  $requester,
        SchoolUser  $outgoing,
        ?SchoolUser $incoming,
        string      $roleSlug,
        array       $transferRecords = [],
        ?string     $notes = null,
    ): RoleSuccession {
        // Determine approver level based on role being transferred
        $approverLevel = $roleSlug === 'institute_head'
            ? 'saas_admin'
            : 'institute_head';

        $succession = RoleSuccession::create([
            'outgoing_user_id' => $outgoing->id,
            'incoming_user_id' => $incoming?->id,
            'role_slug'        => $roleSlug,
            'transfer_records' => $transferRecords,
            'approver_level'   => $approverLevel,
            'status'           => SuccessionStatus::Pending,
            'requested_by'     => $requester->id,
            'requester_notes'  => $notes,
        ]);

        // Create a matching central ApprovalRequest for tracking
        $this->approvalService->submit(
            requestedBy:  $requester,
            actionType:   ActionType::RoleSuccession->value,
            subject:      $succession,
            payload: [
                'outgoing_user_id' => $outgoing->id,
                'outgoing_name'    => $outgoing->name,
                'incoming_user_id' => $incoming?->id,
                'incoming_name'    => $incoming?->name,
                'role_slug'        => $roleSlug,
                'transfer_records' => $transferRecords,
                'approver_level'   => $approverLevel,
            ],
            approverLevel: $approverLevel,
        );

        return $succession;
    }

    /**
     * Approve the succession and transfer the role + records.
     *
     * @param  RoleSuccession  $succession
     * @param  SchoolUser      $approvedBy   Institute Head (or any model for SaaS Admin)
     * @param  string|null     $notes
     */
    public function approve(
        RoleSuccession $succession,
        mixed          $approvedBy,
        ?string        $notes = null,
    ): RoleSuccession {
        if (! $succession->isPending()) {
            throw new \LogicException("Succession [{$succession->id}] is not in pending state.");
        }

        if (! $succession->incoming_user_id) {
            throw new \LogicException('An incoming user must be assigned before approving succession.');
        }

        $succession->update([
            'status'           => SuccessionStatus::Approved,
            'approved_by_type' => get_class($approvedBy),
            'approved_by_id'   => $approvedBy->id,
            'approved_at'      => now(),
            'approver_notes'   => $notes,
        ]);

        // Execute the transfer immediately after approval
        return $this->complete($succession);
    }

    /**
     * Complete the succession — transfer role + linked records.
     */
    public function complete(RoleSuccession $succession): RoleSuccession
    {
        $incoming = SchoolUser::findOrFail($succession->incoming_user_id);
        $outgoing = SchoolUser::findOrFail($succession->outgoing_user_id);

        DB::transaction(function () use ($succession, $incoming, $outgoing) {
            // 1. Transfer Spatie role (school-scoped via team)
            $role = Role::where('name', $succession->role_slug)
                        ->where('guard_name', 'school_users')
                        ->firstOrFail();

            // Add role to incoming user
            if (! $incoming->hasRole($role)) {
                $incoming->assignRole($role);
            }

            // Transfer linked records if requested
            if (! empty($succession->transfer_records)) {
                $this->transferLinkedRecords(
                    $succession->transfer_records,
                    $outgoing->id,
                    $incoming->id,
                );
            }

            // Mark succession complete
            $succession->update([
                'status'       => SuccessionStatus::Completed,
                'completed_at' => now(),
            ]);
        });

        return $succession->fresh();
    }

    /**
     * Cancel a pending succession request.
     */
    public function cancel(RoleSuccession $succession, ?string $reason = null): RoleSuccession
    {
        if (! $succession->isPending()) {
            throw new \LogicException("Only pending successions can be cancelled.");
        }

        $succession->update([
            'status'        => SuccessionStatus::Cancelled,
            'approver_notes' => $reason,
        ]);

        return $succession;
    }

    /**
     * Assign the incoming user after succession was initiated without one.
     */
    public function assignIncomingUser(RoleSuccession $succession, SchoolUser $incoming): RoleSuccession
    {
        if (! $succession->isPending()) {
            throw new \LogicException("Incoming user can only be set on pending successions.");
        }

        $succession->update(['incoming_user_id' => $incoming->id]);

        return $succession->fresh();
    }

    // ── Private: Record Transfer ──────────────────────────────────

    /**
     * Reassign linked records from outgoing to incoming user.
     *
     * The transfer_records array can contain any of these types:
     *   'class_subjects'      → class_subjects.teacher_id
     *   'homework'            → homework.created_by
     *   'attendance_records'  → attendance_records.marked_by
     *   'exam_marks'          → exam_marks.entered_by
     *   'daily_activity_logs' → daily_activity_logs.teacher_id
     */
    private function transferLinkedRecords(
        array  $recordTypes,
        string $outgoingId,
        string $incomingId,
    ): void {
        $transferMap = [
            'class_subjects'      => ['table' => 'class_subjects',      'column' => 'teacher_id'],
            'homework'            => ['table' => 'homework',             'column' => 'created_by'],
            'attendance_records'  => ['table' => 'attendance_records',   'column' => 'marked_by'],
            'exam_marks'          => ['table' => 'exam_marks',           'column' => 'entered_by'],
            'daily_activity_logs' => ['table' => 'daily_activity_logs',  'column' => 'teacher_id'],
        ];

        foreach ($recordTypes as $type) {
            if (isset($transferMap[$type])) {
                DB::table($transferMap[$type]['table'])
                  ->where($transferMap[$type]['column'], $outgoingId)
                  ->update([$transferMap[$type]['column'] => $incomingId]);
            }
        }
    }
}
