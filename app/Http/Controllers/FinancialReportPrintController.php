<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\Expense;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\StudentFee;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders an international-standard, print-friendly financial report
 * with full school header, prepared-by, time-stamped window, and a
 * fully-itemised P&L. Invoked by the "Print" button on the Financial
 * Report page; opens in a new tab so the user gets browser-native
 * print to PDF / Save As, with no chrome to manually hide.
 */
class FinancialReportPrintController extends Controller
{
    public function show(Request $request): View
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->query('to',   now()->endOfMonth()->toDateString()))->endOfDay();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // Only count APPROVED expenses for the official report. Pending
        // expenses are NOT recognized financially until approved, per
        // standard accounting practice.
        $expenseBase = Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->where('approval_status', 'approved');

        $totalExpenses = (int) (clone $expenseBase)->sum('amount_paisas');
        $expensesCount = (int) (clone $expenseBase)->count();

        // Income
        $billed   = (int) StudentFee::query()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount_paisas');
        $collected = (int) FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_amount_paisas');
        $paymentsCount = (int) FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        // Snapshot outstanding (current, all-time pending + partial)
        $outstanding = (int) StudentFee::query()
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('coalesce(sum(amount_paisas + fine_paisas - paid_paisas - discount_paisas), 0) as t')
            ->value('t');

        // Income by fee type
        $byFeeType = StudentFee::query()
            ->whereBetween('student_fees.due_date', [$from->toDateString(), $to->toDateString()])
            ->join('fee_types', 'fee_types.id', '=', 'student_fees.fee_type_id')
            ->selectRaw('fee_types.name as label, sum(student_fees.amount_paisas) as billed, sum(student_fees.paid_paisas) as collected, count(*) as cnt')
            ->groupBy('fee_types.name')
            ->orderByDesc('collected')
            ->get();

        // Income by class
        $byClass = StudentFee::query()
            ->whereBetween('student_fees.due_date', [$from->toDateString(), $to->toDateString()])
            ->join('students', 'students.id', '=', 'student_fees.student_id')
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->selectRaw("coalesce(classes.name, '—') as label, sum(student_fees.amount_paisas) as billed, sum(student_fees.paid_paisas) as collected")
            ->groupBy('classes.name')
            ->orderByDesc('collected')
            ->get();

        // Payments by method
        $byMethod = FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('payment_method as label, sum(total_amount_paisas) as total, count(*) as cnt')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // Expenses by category
        $byExpenseCategory = (clone $expenseBase)
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->selectRaw("coalesce(expense_categories.name, 'Uncategorised') as label, sum(expenses.amount_paisas) as total, count(*) as cnt")
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        // Top 20 individual expenses
        $topExpenses = (clone $expenseBase)
            ->with('category', 'recorder', 'approver')
            ->orderByDesc('amount_paisas')
            ->limit(20)
            ->get();

        // Pending expenses (not yet in the totals — disclosed for transparency)
        $pendingExpenses = Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->where('approval_status', 'pending')
            ->with('category', 'recorder')
            ->orderByDesc('amount_paisas')
            ->get();

        $tenant = function_exists('tenant') ? tenant() : null;
        $user = auth('school_users')->user() ?? auth()->user();

        // Campus context — name from CmsSettings or first campus.
        $campusNames = Campus::query()->orderBy('name')->pluck('name')->all();
        $campusLabel = empty($campusNames)
            ? '—'
            : (count($campusNames) === 1 ? $campusNames[0] : implode(', ', $campusNames));

        $year = AcademicYear::query()->where('is_current', true)->first();

        return view('reports.financial-print', [
            'window' => [
                'from'  => $from,
                'to'    => $to,
                'label' => $from->format('d M Y') . ' — ' . $to->format('d M Y'),
            ],
            'school' => [
                'name'    => $tenant?->school_name ?? config('app.name', 'School'),
                'tagline' => optional($tenant)->tagline,
                'address' => optional($tenant)->address,
                'phone'   => optional($tenant)->phone,
                'email'   => optional($tenant)->email,
                'campus'  => $campusLabel,
                'year'    => $year?->name ?? '—',
            ],
            'preparedBy' => [
                'name'      => $user?->name ?? 'System',
                'role'      => (string) ($user?->active_role ?? $user?->roles?->first()?->name ?? '—'),
                'generated' => now()->format('d M Y · H:i'),
            ],
            'totals' => [
                'billed'         => $billed,
                'collected'      => $collected,
                'expenses'       => $totalExpenses,
                'net'            => $collected - $totalExpenses,
                'outstanding'    => $outstanding,
                'payments_count' => $paymentsCount,
                'expenses_count' => $expensesCount,
            ],
            'by_fee_type'         => $byFeeType,
            'by_class'            => $byClass,
            'by_payment_method'   => $byMethod,
            'by_expense_category' => $byExpenseCategory,
            'top_expenses'        => $topExpenses,
            'pending_expenses'    => $pendingExpenses,
        ]);
    }
}
