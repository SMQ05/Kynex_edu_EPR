<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExpenseResource\Pages;

use App\Enums\ExpenseApprovalStatus;
use App\Filament\SchoolAdmin\Resources\ExpenseResource;
use App\Models\Tenant\Budget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert PKR to paisas if amount_pkr is present
        if (isset($data['amount_pkr'])) {
            $data['amount_paisas'] = (int) (($data['amount_pkr'] ?? 0) * 100);
            unset($data['amount_pkr']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $expense = $this->record;

        // If approved and has budget, ensure budget spent is up to date
        if ($expense->approval_status === ExpenseApprovalStatus::Approved && $expense->budget_id) {
            $budget = Budget::find($expense->budget_id);
            if ($budget) {
                // Recalculate budget spent from all approved expenses
                $totalSpent = $budget->expenses()
                    ->where('approval_status', ExpenseApprovalStatus::Approved->value)
                    ->sum('amount_paisas');
                $budget->update(['spent_amount_paisas' => $totalSpent]);
            }
        }
    }
        protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
}
