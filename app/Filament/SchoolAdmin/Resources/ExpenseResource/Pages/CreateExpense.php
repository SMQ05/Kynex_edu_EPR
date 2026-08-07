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

        // Convert PKR rupees to paisas. The form's amount_pkr field
        // is a virtual rupee value; we persist amount_paisas as int.
        $amountPaisas = (int) round(((float) ($data['amount_pkr'] ?? 0)) * 100);

        if ($amountPaisas <= 0) {
            // Block save — Filament will surface this in the notification
            // because Eloquent will reject the row anyway. Better to fail
            // loudly here than silently store zero.
            throw new \Illuminate\Validation\ValidationException(
                validator: \Illuminate\Support\Facades\Validator::make([], []),
                response: null,
            );
        }

        $data['amount_paisas'] = $amountPaisas;
        $data['recorded_by']   = $user->id;
        unset($data['amount_pkr']);

        // Approval policy: every expense starts as Pending. The only
        // bypass is for users with `bypass_approvals` (institute head /
        // multi-institute head). Everyone else — accountants, admins,
        // bursars — must wait for institute-head approval.
        $activeRole = (string) ($user->active_role ?? $user->roles?->first()?->name ?? '');
        $isHead = in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);

        $data['approval_status'] = $isHead
            ? ExpenseApprovalStatus::Approved->value
            : ExpenseApprovalStatus::Pending->value;

        if ($isHead) {
            $data['approved_by'] = $user->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $expense = $this->record;

        if ($expense->approval_status === ExpenseApprovalStatus::Pending
            || (string) $expense->approval_status === 'pending'
        ) {
            // Open an ApprovalRequest so the institute head sees it in
            // the Approval Queue and can decide.
            app(ApprovalService::class)->submit(
                requestedBy: auth('school_users')->user() ?? auth()->user(),
                actionType: 'expense_approval',
                subject: $expense,
                payload: [
                    'expense_id' => $expense->id,
                    'title'      => $expense->title,
                    'category'   => $expense->category?->name,
                    'amount_pkr' => number_format($expense->amount_paisas / 100, 2),
                    'date'       => optional($expense->expense_date)->toDateString(),
                ],
            );

            Notification::make()
                ->title('Expense submitted for approval')
                ->body('It will appear under the Approval Queue and reflect in reports once the Institute Head approves it.')
                ->warning()
                ->send();
            return;
        }

        // Institute-head-recorded expense: auto-approved, update budget.
        if ($expense->budget_id) {
            $budget = Budget::find($expense->budget_id);
            if ($budget) {
                $budget->increment('spent_amount_paisas', $expense->amount_paisas);
            }
        }

        Notification::make()
            ->title('Expense recorded and approved')
            ->success()
            ->send();
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
}
