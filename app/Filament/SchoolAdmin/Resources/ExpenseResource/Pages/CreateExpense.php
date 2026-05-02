<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExpenseResource\Pages;

use App\Enums\ExpenseApprovalStatus;
use App\Filament\SchoolAdmin\Resources\ExpenseResource;
use App\Models\Tenant\Budget;
use App\Services\ApprovalService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth('school_users')->user() ?? auth()->user();

        if (! $user) {
            abort(403, 'You must be signed in as a school user to record expenses.');
        }

        // Convert PKR to paisas
        $amountPaisas = (int) (($data['amount_pkr'] ?? 0) * 100);

        $data['amount_paisas'] = $amountPaisas;
        $data['recorded_by'] = $user->id;

        // Remove virtual field
        unset($data['amount_pkr']);

        // Determine approval status
        $threshold = 50000 * 100; // PKR 50,000 in paisas

        try {
            $hasBypass = method_exists($user, 'hasPermissionTo')
                && $user->hasPermissionTo('bypass_approvals', 'school_users');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            $hasBypass = false;
        }

        if ($amountPaisas > $threshold && ! $hasBypass) {
            $data['approval_status'] = ExpenseApprovalStatus::Pending->value;
        } else {
            $data['approval_status'] = ExpenseApprovalStatus::Approved->value;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $expense = $this->record;
        $threshold = 50000 * 100;

        if ($expense->amount_paisas > $threshold && $expense->approval_status === ExpenseApprovalStatus::Pending) {
            // Submit for approval
            app(ApprovalService::class)->submit(
                requestedBy: auth()->user(),
                actionType: 'large_expense',
                subject: $expense,
                payload: [
                    'expense_id' => $expense->id,
                    'title' => $expense->title,
                    'amount_pkr' => number_format($expense->amount_paisas / 100, 2),
                ],
            );

            Notification::make()
                ->title('Expense submitted for approval')
                ->body('This expense exceeds PKR 50,000 and requires approval before being applied to the budget.')
                ->warning()
                ->send();
        } else {
            // Auto-approved — update budget spent amount
            if ($expense->budget_id) {
                $budget = Budget::find($expense->budget_id);
                if ($budget) {
                    $budget->increment('spent_amount_paisas', $expense->amount_paisas);
                }
            }

            Notification::make()
                ->title('Expense recorded')
                ->body('The expense has been approved and recorded successfully.')
                ->success()
                ->send();
        }
    }
}
