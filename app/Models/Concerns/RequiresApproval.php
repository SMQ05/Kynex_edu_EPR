<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * RequiresApproval — Adds approval workflow hooks to any model.
 *
 * Models using this trait can submit actions for approval and
 * query their related approval requests from the central database.
 */
trait RequiresApproval
{
    /**
     * Get all approval requests where this model is the subject.
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'subject');
    }

    /**
     * Submit an approval request for an action on this model.
     */
    public function submitForApproval(
        string $actionType,
        array $payload = [],
        ?\DateTimeInterface $expiresAt = null,
    ): ApprovalRequest {
        $user = auth()->guard('school_users')->user()
            ?? auth()->user();

        return app(ApprovalService::class)->submit(
            requestedBy: $user,
            actionType: $actionType,
            subject: $this,
            payload: $payload,
            expiresAt: $expiresAt,
        );
    }

    /**
     * Check if this model has a pending approval for a given action type.
     */
    public function hasPendingApproval(string $actionType): bool
    {
        return $this->approvalRequests()
            ->where('action_type', $actionType)
            ->where('status', ApprovalStatus::Pending)
            ->exists();
    }

    /**
     * Get the latest approval request for a given action type.
     */
    public function latestApproval(string $actionType): ?ApprovalRequest
    {
        return $this->approvalRequests()
            ->where('action_type', $actionType)
            ->latest()
            ->first();
    }
}
