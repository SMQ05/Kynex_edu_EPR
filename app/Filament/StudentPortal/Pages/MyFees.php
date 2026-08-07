<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use App\Support\SchoolSettings;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Tuition statement: what is owed, what has been paid, and the receipt trail.
 *
 * There is no balance column on student_fees. The model already derives one:
 * net_payable_paisas (amount + fine - discount) and balance_paisas
 * (net_payable - paid). This page uses those accessors rather than repeating
 * the arithmetic, so a change to how a balance is defined lands in one place.
 */
class MyFees extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'My Fees';

    protected string $view = 'filament.student-portal.pages.my-fees';

    public function getHeading(): string
    {
        return 'My Fees';
    }

    public function currency(): string
    {
        return (string) SchoolSettings::get('currency.symbol', '$');
    }

    /** Every fee line for this student, most recent due date first. */
    #[Computed]
    public function fees(): Collection
    {
        return StudentFee::query()
            ->with('feeType')
            ->where('student_id', $this->studentId())
            ->orderByDesc('due_date')
            ->get();
    }

    /** Receipts, newest first. */
    #[Computed]
    public function payments(): Collection
    {
        return FeePayment::query()
            ->where('student_id', $this->studentId())
            ->orderByDesc('payment_date')
            ->limit(25)
            ->get();
    }

    /** Statement totals, all derived from the fee lines. */
    #[Computed]
    public function totals(): array
    {
        $billed = 0;
        $paid = 0;
        $due = 0;
        $overdue = 0;
        $today = now()->startOfDay();

        foreach ($this->fees as $fee) {
            $lineBilled = $fee->net_payable_paisas;
            $linePaid = (int) $fee->paid_paisas;
            $lineDue = max(0, $fee->balance_paisas);

            $billed += $lineBilled;
            $paid += $linePaid;
            $due += $lineDue;

            if ($lineDue > 0 && $fee->due_date && $today->greaterThan($fee->due_date)) {
                $overdue += $lineDue;
            }
        }

        return [
            'billed' => $billed,
            'paid' => $paid,
            'due' => $due,
            'overdue' => $overdue,
            'paid_ratio' => $billed > 0 ? round($paid / $billed * 100, 1) : null,
        ];
    }

    /** Per-line derived amounts, so the view stays free of arithmetic. */
    public function lineFor(StudentFee $fee): array
    {
        $billed = $fee->net_payable_paisas;
        $paid = (int) $fee->paid_paisas;
        $due = max(0, $fee->balance_paisas);
        $isOverdue = $due > 0 && $fee->due_date && now()->startOfDay()->greaterThan($fee->due_date);

        return [
            'billed' => $billed,
            'paid' => $paid,
            'due' => $due,
            'overdue' => $isOverdue,
        ];
    }
}
