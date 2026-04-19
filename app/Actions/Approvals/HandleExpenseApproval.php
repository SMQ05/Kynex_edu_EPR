<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\ExpenseApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\Tenant\Expense;
use App\Models\Tenant\InAppNotification;

/**
 * HandleExpenseApproval — Executed when an expense_approval is approved.
 *
 * Phase 10.3 — For expenses exceeding PKR 50,000 (5,000,000 paisas).
 *
 * 1. Validates the expense amount exceeds the approval threshold.
 * 2. Marks the Expense as approved.
 * 3. Notifies the requester via in-app notification.
 */
class HandleExpenseApproval
{
    public function handle(ApprovalRequest $approval): void
    {
        $expense = Expense::findOrFail($approval->payload['expense_id']);

        // Guard: expenses <= PKR 500 (50,000 paisas) should not go through approval
        if ($expense->amount_paisas <= 5000000) {
            throw new \RuntimeException(
                "Expense amount ({$expense->amount_paisas} paisas) does not exceed the PKR 50,000 approval threshold."
            );
        }

        // 1. Approve the expense
        $expense->update([
            'approval_status' => ExpenseApprovalStatus::Approved,
            'approved_by'     => $approval->reviewed_by_id,
        ]);

        // 2. Notify requester
        $amountFormatted = 'PKR ' . number_format($expense->amount_paisas / 100, 2);

        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Expense Approved',
            'body'    => "Expense \"{$expense->title}\" ({$amountFormatted}) has been approved.",
            'type'    => 'success',
        ]);
    }
}
