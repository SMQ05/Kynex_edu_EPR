<?php

declare(strict_types=1);

namespace App\Filament\ParentPortal\Pages;

use App\Models\Tenant\BankPaymentRequest;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use App\Support\SchoolSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Fee statement and payment for a guardian's children.
 *
 * HOW PAYING WORKS. There is a PaymentGatewayInterface with Stripe, EasyPaisa
 * and JazzCash drivers, but none is configured for this tenant, so a card
 * charge cannot be taken. Rather than fake one, this submits a
 * BankPaymentRequest: the parent records what they paid and the reference, the
 * school reviews it in the admin panel, and BankPaymentRequest::approve()
 * allocates it against outstanding fees through FeesService and issues a real
 * receipt. That is a genuine end-to-end flow, and it is how a large share of
 * school fees are actually settled.
 *
 * If a gateway is configured later, the card path can sit alongside this
 * without changing the statement or the allocation logic.
 */
class Fees extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Fees & Payments';

    protected string $view = 'filament.parent-portal.pages.fees';

    /** Which child's statement is open. */
    #[Url(as: 'child')]
    public ?string $childId = null;

    // ── Payment form state ──────────────────────────────────────────
    public string $amount = '';
    public string $bankReference = '';
    public string $paidOn = '';
    public string $note = '';
    public bool $showForm = false;

    public function mount(): void
    {
        $this->paidOn = now()->toDateString();

        // Default to the child with the largest outstanding balance, not the
        // alphabetically first. Otherwise a family whose eldest owes money but
        // whose youngest is settled lands on "nothing to pay" and the payment
        // action is hidden behind a switcher they may not notice.
        $this->childId ??= $this->children
            ->sortByDesc(fn ($c) => $this->outstandingFor($c))
            ->first()?->id;
    }

    public function getHeading(): string
    {
        return 'Fees & Payments';
    }

    /** Outstanding balance for one child, in minor units. */
    protected function outstandingFor(Student $student): int
    {
        return (int) StudentFee::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'partial'])
            ->get()
            ->sum(fn (StudentFee $f) => max(0, $f->balance_paisas));
    }

    public function currency(): string
    {
        return (string) SchoolSettings::get('currency.symbol', '$');
    }

    /**
     * The guardian's own children.
     *
     * Same match as the dashboard: linked by guardian.school_user_id, or by the
     * guardian email matching the signed-in account.
     */
    #[Computed]
    public function children(): Collection
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return collect();
        }

        return Student::query()
            ->whereHas('guardians', fn ($q) => $q
                ->where('school_user_id', $user->id)
                ->orWhere('email', $user->email))
            ->with(['schoolClass', 'section'])
            ->orderBy('first_name')
            ->get();
    }

    /**
     * The open child — re-resolved from the guardian's own list, so a
     * hand-edited ?child= cannot open another family's statement.
     */
    #[Computed]
    public function child(): ?Student
    {
        return $this->children->firstWhere('id', $this->childId) ?? $this->children->first();
    }

    public function selectChild(string $id): void
    {
        $this->childId = $id;
        $this->showForm = false;
        unset($this->child, $this->fees, $this->payments, $this->requests, $this->totals);
    }

    #[Computed]
    public function fees(): Collection
    {
        $child = $this->child;

        return $child
            ? StudentFee::where('student_id', $child->id)->with('feeType')->orderByDesc('due_date')->get()
            : collect();
    }

    #[Computed]
    public function payments(): Collection
    {
        $child = $this->child;

        return $child
            ? FeePayment::where('student_id', $child->id)->orderByDesc('payment_date')->limit(20)->get()
            : collect();
    }

    /** The family's own submitted bank payment requests, newest first. */
    #[Computed]
    public function requests(): Collection
    {
        $child = $this->child;

        return $child
            ? BankPaymentRequest::where('student_id', $child->id)->orderByDesc('created_at')->limit(10)->get()
            : collect();
    }

    /** @return array{billed:int,paid:int,due:int,overdue:int,paidRatio:?float} */
    #[Computed]
    public function totals(): array
    {
        $billed = 0;
        $paid = 0;
        $due = 0;
        $overdue = 0;
        $today = now()->startOfDay();

        foreach ($this->fees as $fee) {
            $billed += $fee->net_payable_paisas;
            $paid += (int) $fee->paid_paisas;
            $balance = max(0, $fee->balance_paisas);
            $due += $balance;

            if ($balance > 0 && $fee->due_date && $today->greaterThan($fee->due_date)) {
                $overdue += $balance;
            }
        }

        return [
            'billed' => $billed,
            'paid' => $paid,
            'due' => $due,
            'overdue' => $overdue,
            'paidRatio' => $billed > 0 ? round($paid / $billed * 100, 1) : null,
        ];
    }

    public function lineFor(StudentFee $fee): array
    {
        $balance = max(0, $fee->balance_paisas);

        return [
            'billed' => $fee->net_payable_paisas,
            'paid' => (int) $fee->paid_paisas,
            'due' => $balance,
            'overdue' => $balance > 0 && $fee->due_date && now()->startOfDay()->greaterThan($fee->due_date),
        ];
    }

    public function openForm(): void
    {
        // Pre-fill with the full outstanding balance — the common case.
        $this->amount = number_format($this->totals['due'] / 100, 2, '.', '');
        $this->bankReference = '';
        $this->note = '';
        $this->paidOn = now()->toDateString();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    /**
     * Record a bank transfer for review.
     *
     * Validated tightly because a guardian is submitting a financial claim:
     * the amount must be positive, must not exceed what is actually owed, and
     * a reference is required so the bursar can match it against the statement.
     */
    public function submitPayment(): void
    {
        $child = $this->child;
        if (! $child) {
            return;
        }

        $user = auth()->guard('school_users')->user();
        $due = $this->totals['due'];

        $amountPaisas = (int) round(((float) str_replace(',', '', $this->amount)) * 100);
        $reference = trim($this->bankReference);

        if ($amountPaisas <= 0) {
            Notification::make()->title('Enter the amount you paid.')->warning()->send();

            return;
        }

        if ($amountPaisas > $due) {
            Notification::make()
                ->title('That is more than the outstanding balance')
                ->body('The balance is ' . $this->currency() . number_format($due / 100, 2) . '. Contact the office if you believe this is wrong.')
                ->warning()
                ->send();

            return;
        }

        if ($reference === '') {
            Notification::make()
                ->title('A bank reference is required')
                ->body('Use the reference from your transfer so the school can match the payment.')
                ->warning()
                ->send();

            return;
        }

        BankPaymentRequest::create([
            'id' => (string) Str::ulid(),
            'student_id' => $child->id,
            'amount_paisas' => $amountPaisas,
            'bank_reference' => $reference,
            'paid_on' => $this->paidOn ?: now()->toDateString(),
            'status' => 'pending',
            'note' => $this->note !== '' ? $this->note : null,
            'requested_by' => $user?->id,
            'created_by' => $user?->id,
        ]);

        Notification::make()
            ->title('Payment submitted for confirmation')
            ->body('The school will verify the transfer and issue a receipt. You will see it here once confirmed.')
            ->success()
            ->send();

        $this->showForm = false;
        unset($this->requests, $this->fees, $this->payments, $this->totals);
    }
}
