<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Expense;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Total Financial Report — enterprise-grade P&L for schools.
 *
 * The page supports two modes:
 *  - "summary": KPI tiles, headline numbers
 *  - "detailed": full breakdown — daily cash flow, by fee type, by class,
 *                 by payment method, by expense category, top expenses,
 *                 defaulter aging, monthly P&L
 *
 * Date filtering is by EXPLICIT date range (from / to), pre-set ranges
 * (this month, last month, this year, last year, academic year), and
 * persists in URL so the report can be shared / bookmarked / printed
 * with the same window.
 */
class FinancialReport extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_financial_reports';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Total Financial Report';

    protected string $view = 'filament.school-admin.pages.financial-report';

    /** @var 'summary'|'detailed' */
    #[Url(as: 'mode')]
    public string $mode = 'summary';

    /** ISO date strings (YYYY-MM-DD). */
    #[Url(as: 'from')] public ?string $from = null;
    #[Url(as: 'to')]   public ?string $to   = null;

    /** Preset shortcuts: month / last_month / year / last_year / ay / custom. */
    #[Url(as: 'preset')] public string $preset = 'month';

    public function mount(): void
    {
        if (! $this->from || ! $this->to) {
            $this->applyPreset('month');
        }
    }

    public function getTitle(): string
    {
        return 'Financial Report';
    }

    public function getSubheading(): ?string
    {
        return 'Pick a date range or a preset · switch between Summary and Detailed · click Print to render an A4-friendly version.';
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['summary', 'detailed'], true) ? $mode : 'summary';
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;
        switch ($preset) {
            case 'month':
                $this->from = now()->startOfMonth()->toDateString();
                $this->to   = now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->from = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $this->to   = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case 'year':
                $this->from = now()->startOfYear()->toDateString();
                $this->to   = now()->endOfYear()->toDateString();
                break;
            case 'last_year':
                $this->from = now()->subYear()->startOfYear()->toDateString();
                $this->to   = now()->subYear()->endOfYear()->toDateString();
                break;
            case 'ay':
                $year = AcademicYear::query()->where('is_current', true)->first();
                $this->from = $year?->start_date ? Carbon::parse($year->start_date)->toDateString() : now()->subYear()->toDateString();
                $this->to   = $year?->end_date   ? Carbon::parse($year->end_date)->toDateString()   : now()->toDateString();
                break;
            case 'last_30':
                $this->from = now()->subDays(29)->toDateString();
                $this->to   = now()->toDateString();
                break;
            case 'last_90':
                $this->from = now()->subDays(89)->toDateString();
                $this->to   = now()->toDateString();
                break;
            case 'custom':
            default:
                $this->preset = 'custom';
                break;
        }
    }

    public function applyCustomRange(): void
    {
        $this->preset = 'custom';
    }

    /**
     * @return array{from:Carbon,to:Carbon,label:string}
     */
    public function window(): array
    {
        $from = $this->from ? Carbon::parse($this->from)->startOfDay() : now()->startOfMonth();
        $to   = $this->to   ? Carbon::parse($this->to)->endOfDay()     : now()->endOfMonth();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        return [
            'from'  => $from,
            'to'    => $to,
            'label' => $from->format('d M Y') . ' — ' . $to->format('d M Y'),
        ];
    }

    /** @return array<string,mixed> */
    public function summaryData(): array
    {
        ['from' => $from, 'to' => $to, 'label' => $label] = $this->window();

        // Income — billed in window (StudentFee.due_date in window)
        $billed = (int) StudentFee::query()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount_paisas');

        // Income — collected in window (FeePayment.payment_date in window)
        $collected = (int) FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_amount_paisas');

        $paymentsCount = (int) FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        // Outstanding now (unaffected by window — current snapshot)
        $outstanding = (int) StudentFee::query()
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('coalesce(sum(amount_paisas + fine_paisas - paid_paisas - discount_paisas), 0) as t')
            ->value('t');

        // Expenses in window
        $expenses = (int) Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount_paisas');

        $expensesCount = (int) Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        // Discounts + fines in window
        $discounts = (int) StudentFee::query()
            ->whereBetween('updated_at', [$from, $to])
            ->sum('discount_paisas');

        $fines = (int) StudentFee::query()
            ->whereBetween('updated_at', [$from, $to])
            ->sum('fine_paisas');

        $net = $collected - $expenses;

        return [
            'window_label'    => $label,
            'billed'          => self::pkr($billed),
            'collected'       => self::pkr($collected),
            'expenses'        => self::pkr($expenses),
            'net'             => self::pkr($net),
            'net_negative'    => $net < 0,
            'discounts'       => self::pkr($discounts),
            'fines'           => self::pkr($fines),
            'outstanding'     => self::pkr($outstanding),
            'collection_rate' => $billed > 0 ? round($collected * 100 / $billed, 1) : 0.0,
            'payments_count'  => $paymentsCount,
            'expenses_count'  => $expensesCount,
            'avg_payment_pkr' => $paymentsCount > 0 ? 'PKR ' . number_format(($collected / $paymentsCount) / 100, 2) : 'PKR 0.00',
        ];
    }

    /** @return array<string,mixed> */
    public function detailedData(): array
    {
        ['from' => $from, 'to' => $to, 'label' => $label] = $this->window();

        // ── Daily cash-flow (income, expenses, net) ───────────────────
        $days = [];
        $cursor = $from->copy();
        while ($cursor <= $to) {
            $days[$cursor->toDateString()] = [
                'date'      => $cursor->format('d M'),
                'collected' => 0,
                'expenses'  => 0,
                'net'       => 0,
            ];
            $cursor->addDay();
        }

        $payRows = FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("payment_date::date as d, sum(total_amount_paisas) as t")
            ->groupBy('d')->pluck('t', 'd')->all();
        foreach ($payRows as $d => $t) {
            $key = (string) (\Carbon\Carbon::parse($d)->toDateString());
            if (isset($days[$key])) $days[$key]['collected'] = (int) $t;
        }

        $expRows = Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("expense_date::date as d, sum(amount_paisas) as t")
            ->groupBy('d')->pluck('t', 'd')->all();
        foreach ($expRows as $d => $t) {
            $key = (string) (\Carbon\Carbon::parse($d)->toDateString());
            if (isset($days[$key])) $days[$key]['expenses'] = (int) $t;
        }
        foreach ($days as &$row) { $row['net'] = $row['collected'] - $row['expenses']; }
        unset($row);

        // ── By fee type (income) ──────────────────────────────────────
        $byFeeType = StudentFee::query()
            ->whereBetween('student_fees.due_date', [$from->toDateString(), $to->toDateString()])
            ->join('fee_types', 'fee_types.id', '=', 'student_fees.fee_type_id')
            ->selectRaw('fee_types.name as label, sum(student_fees.amount_paisas) as billed, sum(student_fees.paid_paisas) as collected, count(*) as cnt')
            ->groupBy('fee_types.name')
            ->orderByDesc('billed')
            ->get()
            ->map(fn ($r) => [
                'label'     => $r->label,
                'billed'    => (int) $r->billed,
                'collected' => (int) $r->collected,
                'count'     => (int) $r->cnt,
                'rate'      => $r->billed > 0 ? round(((int) $r->collected) * 100 / (int) $r->billed, 1) : 0,
            ])->all();

        // ── By class (income) ─────────────────────────────────────────
        $byClass = StudentFee::query()
            ->whereBetween('student_fees.due_date', [$from->toDateString(), $to->toDateString()])
            ->join('students', 'students.id', '=', 'student_fees.student_id')
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->selectRaw("coalesce(classes.name, '—') as label, sum(student_fees.amount_paisas) as billed, sum(student_fees.paid_paisas) as collected")
            ->groupBy('classes.name')
            ->orderByDesc('billed')
            ->get()
            ->map(fn ($r) => [
                'label'     => $r->label,
                'billed'    => (int) $r->billed,
                'collected' => (int) $r->collected,
                'rate'      => $r->billed > 0 ? round(((int) $r->collected) * 100 / (int) $r->billed, 1) : 0,
            ])->all();

        // ── By payment method ─────────────────────────────────────────
        $byMethod = FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('payment_method as label, sum(total_amount_paisas) as total, count(*) as cnt')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'label' => ucfirst((string) $r->label),
                'total' => (int) $r->total,
                'count' => (int) $r->cnt,
            ])->all();

        // ── Expenses by category ──────────────────────────────────────
        $byExpenseCategory = Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->selectRaw("coalesce(expense_categories.name, 'Uncategorised') as label, sum(expenses.amount_paisas) as total, count(*) as cnt")
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'label' => $r->label,
                'total' => (int) $r->total,
                'count' => (int) $r->cnt,
            ])->all();

        // ── Top 10 individual expenses ────────────────────────────────
        $topExpenses = Expense::query()
            ->with('category')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('amount_paisas')
            ->limit(10)
            ->get()
            ->map(fn ($e) => [
                'date'        => Carbon::parse($e->expense_date)->format('d M Y'),
                'category'    => $e->category?->name ?? 'Uncategorised',
                'description' => (string) ($e->description ?? $e->title ?? '—'),
                'amount'      => (int) $e->amount_paisas,
            ])->all();

        // ── Defaulter aging (snapshot, today) ─────────────────────────
        $aging = StudentFee::query()
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->selectRaw(<<<'SQL'
                COUNT(*) FILTER (WHERE NOW()::date - due_date BETWEEN 1 AND 30)   AS b1,
                COUNT(*) FILTER (WHERE NOW()::date - due_date BETWEEN 31 AND 60)  AS b2,
                COUNT(*) FILTER (WHERE NOW()::date - due_date BETWEEN 61 AND 90)  AS b3,
                COUNT(*) FILTER (WHERE NOW()::date - due_date > 90)               AS b4,
                COALESCE(SUM(amount_paisas + fine_paisas - paid_paisas - discount_paisas), 0) AS total_due
            SQL)
            ->first();

        // ── Monthly P&L roll-up ───────────────────────────────────────
        $months = [];
        $cur = $from->copy()->startOfMonth();
        $endM = $to->copy()->startOfMonth();
        while ($cur <= $endM) {
            $months[$cur->format('Y-m')] = ['label' => $cur->format('M Y'), 'collected' => 0, 'expenses' => 0, 'net' => 0];
            $cur->addMonth();
        }
        foreach ($payRows as $d => $t) {
            $m = (string) (\Carbon\Carbon::parse($d)->format('Y-m'));
            if (isset($months[$m])) $months[$m]['collected'] += (int) $t;
        }
        foreach ($expRows as $d => $t) {
            $m = (string) (\Carbon\Carbon::parse($d)->format('Y-m'));
            if (isset($months[$m])) $months[$m]['expenses'] += (int) $t;
        }
        foreach ($months as &$m) { $m['net'] = $m['collected'] - $m['expenses']; }
        unset($m);

        return [
            'window_label'        => $label,
            'days'                => array_values($days),
            'months'              => array_values($months),
            'by_fee_type'         => $byFeeType,
            'by_class'            => $byClass,
            'by_payment_method'   => $byMethod,
            'by_expense_category' => $byExpenseCategory,
            'top_expenses'        => $topExpenses,
            'aging'               => [
                'b1' => (int) ($aging->b1 ?? 0),
                'b2' => (int) ($aging->b2 ?? 0),
                'b3' => (int) ($aging->b3 ?? 0),
                'b4' => (int) ($aging->b4 ?? 0),
                'total_due_paisas' => (int) ($aging->total_due ?? 0),
            ],
            'total_collected'     => (int) array_sum(array_column($months, 'collected')),
            'total_expenses'      => (int) array_sum(array_column($months, 'expenses')),
            'net'                 => (int) array_sum(array_column($months, 'net')),
        ];
    }

    public static function pkr(int|float|string|null $paisas): string
    {
        return 'PKR ' . number_format(((int) ($paisas ?? 0)) / 100, 2);
    }
}
