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
     * Reject a pending request and call the handler's handleRejection hook if present.
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

        $fresh = $request->fresh();

        // Call optional per-action rejection hook (e.g. send rejection emails)
        $handlerClass = config("approval.handlers.{$fresh->action_type}");
        if ($handlerClass && class_exists($handlerClass) && method_exists($handlerClass, 'handleRejection')) {
            $weInitialized = false;
            if ($fresh->tenant_id) {
                $tenant = \App\Models\Tenant::find($fresh->tenant_id);
                if ($tenant && (! tenancy()->initialized || tenant()?->id !== $tenant->id)) {
                    tenancy()->initialize($tenant);
                    $weInitialized = true;
                }
            }
            try {
                app($handlerClass)->handleRejection($fresh);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("ApprovalService reject hook failed for {$fresh->id}: {$e->getMessage()}");
            } finally {
                if ($weInitialized && tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        return $fresh;
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
