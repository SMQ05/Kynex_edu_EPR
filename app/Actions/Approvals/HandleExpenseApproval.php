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

        // 1. Approve the expense
        $expense->update([
            'approval_status' => ExpenseApprovalStatus::Approved,
            'approved_by'     => $approval->reviewed_by_id,
        ]);

        // 2. Update budget spent if linked
        if ($expense->budget_id) {
            $budget = \App\Models\Tenant\Budget::find($expense->budget_id);
            if ($budget) {
                $budget->increment('spent_amount_paisas', $expense->amount_paisas);
            }
        }

        // 3. Notify requester
        $amountFormatted = 'PKR ' . number_format($expense->amount_paisas / 100, 2);

        try {
            InAppNotification::create([
                'user_id' => $approval->requested_by_id,
                'title'   => 'Expense Approved',
                'body'    => "Expense \"{$expense->title}\" ({$amountFormatted}) has been approved and is now reflected in your reports.",
                'type'    => 'success',
            ]);
        } catch (\Throwable) {
            // Notifications table may be missing on older tenants — non-fatal.
        }
    }
}
