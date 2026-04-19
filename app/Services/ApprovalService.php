<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Jobs\ExecuteApprovedAction;
use Illuminate\Database\Eloquent\Model;

/**
 * ApprovalService — Creates, reviews, and dispatches approval requests.
 *
 * All approval requests live in the central database. The service
 * handles tenant context by storing the tenant_id on each request.
 */
class ApprovalService
{
    /**
     * Submit a new approval request.
     *
     * @param  string  $approverLevel  'institute_head' (default) | 'saas_admin'
     */
    public function submit(
        Model $requestedBy,
        string $actionType,
        ?Model $subject = null,
        array $payload = [],
        ?\DateTimeInterface $expiresAt = null,
        string $approverLevel = 'institute_head',
    ): ApprovalRequest {
        $tenantId = tenancy()->initialized
            ? tenant()->id
            : null;

        return ApprovalRequest::create([
            'tenant_id'          => $tenantId,
            'requested_by_type'  => $requestedBy->getMorphClass(),
            'requested_by_id'    => $requestedBy->getKey(),
            'action_type'        => $actionType,
            'approver_level'     => $approverLevel,
            'subject_type'       => $subject?->getMorphClass(),
            'subject_id'         => $subject?->getKey(),
            'payload'            => $payload,
            'status'             => ApprovalStatus::Pending,
            'expires_at'         => $expiresAt,
        ]);
    }

    /**
     * Approve a pending request and optionally dispatch execution.
     */
    public function approve(
        ApprovalRequest $request,
        Model $reviewer,
        ?string $adminNote = null,
        bool $autoExecute = true,
    ): ApprovalRequest {
        $request->update([
            'status'            => ApprovalStatus::Approved,
            'reviewed_by_type'  => $reviewer->getMorphClass(),
            'reviewed_by_id'    => $reviewer->getKey(),
            'review_notes'      => $adminNote,
            'reviewed_at'       => now(),
        ]);

        if ($autoExecute) {
            ExecuteApprovedAction::dispatch($request);
        }

        return $request->fresh();
    }

    /**
     * Reject a pending request.
     */
    public function reject(
        ApprovalRequest $request,
        Model $reviewer,
        ?string $adminNote = null,
    ): ApprovalRequest {
        $request->update([
            'status'            => ApprovalStatus::Rejected,
            'reviewed_by_type'  => $reviewer->getMorphClass(),
            'reviewed_by_id'    => $reviewer->getKey(),
            'review_notes'      => $adminNote,
            'reviewed_at'       => now(),
        ]);

        return $request->fresh();
    }

    /**
     * Cancel a pending request (by the requester).
     */
    public function cancel(ApprovalRequest $request): ApprovalRequest
    {
        $request->update([
            'status'      => ApprovalStatus::Cancelled,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }

    /**
     * Get all pending requests for a given tenant.
     */
    public function pendingForTenant(string $tenantId)
    {
        return ApprovalRequest::pending()
            ->forTenant($tenantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all pending requests that require Institute Head approval
     * for a given tenant.
     */
    public function pendingForInstituteHead(string $tenantId)
    {
        return ApprovalRequest::pending()
            ->forTenant($tenantId)
            ->where('approver_level', 'institute_head')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all pending requests escalated to SaaS Super Admin
     * (Institute Head succession, etc.).
     */
    public function pendingForSaasAdmin()
    {
        return ApprovalRequest::pending()
            ->where('approver_level', 'saas_admin')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Expire overdue pending requests.
     */
    public function expireOverdue(): int
    {
        return ApprovalRequest::pending()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => ApprovalStatus::Rejected, 'review_notes' => 'Auto-expired']);
    }
}
