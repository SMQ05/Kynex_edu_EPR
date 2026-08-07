<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * h. FinanceSeeder
 *
 * expense_categories, expenses for Feb-May 2026 (monthly recurring +
 * periodic), staff_payrolls per staff per month, simple budget for
 * the academic year.
 *
 * Approval distribution: 80% approved, 15% pending, 5% rejected.
 */
class FinanceSeeder extends Seeder
{
    use UsesDemoProfile;

    /** category name => id */
    public array $catIdByName = [];
    public string $headId = '';
    public string $accountantId = '';

    public function __construct(
        public StaffSeeder $staff,
    ) {}

    public function run(): void
    {
        $this->headId = $this->staff->userIdByLabel['principal']
            ?? $this->staff->userIdByLabel['admin'];
        $this->accountantId = $this->staff->userIdByLabel['accountant']
            ?? $this->staff->userIdByLabel['admin'];

        $this->seedCategories();
        $this->seedRecurringExpenses();
        $this->seedPeriodicExpenses();
        $this->seedPayrolls();
        // Budgets go LAST: each one's spent_amount_paisas is rolled up from
        // the expenses above, so seeding it earlier (as this used to) left
        // every category showing zero spend against its plan.
        $this->seedBudget();
    }

    protected function seedCategories(): void
    {
        DB::table('expenses')->delete();
        DB::table('budgets')->delete();
        DB::table('expense_categories')->delete();

        $rows = [
            'Salaries' => 'Staff salaries and payroll',
            'Utilities' => 'Electricity, water, gas',
            'Internet & IT' => 'Internet, software subscriptions, IT support',
            'Rent' => 'Building lease',
            'Stationery' => 'Office and classroom stationery',
            'Lab Supplies' => 'Science lab consumables',
            'Sports Equipment' => 'Balls, mats, jerseys, etc.',
            'Library Books' => 'Books and periodicals',
            'Repairs & Maintenance' => 'Building and equipment repairs',
            'Professional Development' => 'Teacher training, certifications',
            'Exam Printing' => 'Exam paper printing and stationery',
        ];
        foreach ($rows as $name => $desc) {
            $id = (string) Str::ulid();
            DB::table('expense_categories')->insert([
                'id' => $id,
                'name' => $name,
                'description' => $desc,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->catIdByName[$name] = $id;
        }
        $this->command?->line('  ✓ expense_categories seeded (' . count($rows) . ')');
    }

    protected function seedBudget(): void
    {
        // One budget per expense category, for the current academic year.
        //
        // The previous version probed for columns named 'name',
        // 'amount_paisas', 'start_date', 'end_date', 'period' and
        // 'description' — none of which exist on this table. It therefore
        // always built a row missing the NOT NULL 'title' and every run
        // ended in "⚠ budgets insert skipped". The real columns are
        // title / budgeted_amount_paisas / spent_amount_paisas / notes.
        //
        // Budgeted is derived from what was actually spent, with a small
        // per-category variance so a budget-vs-actual report shows a mix of
        // under- and over-spend instead of everything landing exactly on plan.
        $yearId = (string) DB::table('academic_years')->where('is_current', true)->value('id');
        if ($yearId === '') {
            $this->command?->warn('  ⚠ budgets skipped: no current academic year.');

            return;
        }

        $spentByCategory = DB::table('expenses')
            ->whereNull('deleted_at')
            ->selectRaw('category_id, COALESCE(SUM(amount_paisas), 0) AS spent')
            ->groupBy('category_id')
            ->pluck('spent', 'category_id');

        $inserted = 0;
        $overBudget = 0;
        foreach ($this->catIdByName as $categoryName => $categoryId) {
            $spent = (int) ($spentByCategory[$categoryId] ?? 0);

            // Categories with no activity still get a nominal plan so the
            // report shows the full chart of accounts, not just active lines.
            $budgeted = $spent > 0
                ? (int) round($spent * (mt_rand(88, 122) / 100))
                : 50_000_00;

            if ($budgeted < $spent) {
                $overBudget++;
            }

            $budgetId = (string) Str::ulid();
            DB::table('budgets')->insert([
                'id' => $budgetId,
                'academic_year_id' => $yearId,
                'category_id' => $categoryId,
                'title' => $categoryName . ' — Annual Plan',
                'budgeted_amount_paisas' => $budgeted,
                'spent_amount_paisas' => $spent,
                'notes' => 'Annual operating budget for ' . $categoryName . '.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Point this category's expenses at their budget so the join
            // actually resolves. expenses.budget_id was left null before.
            DB::table('expenses')
                ->where('category_id', $categoryId)
                ->whereNull('budget_id')
                ->update(['budget_id' => $budgetId]);

            $inserted++;
        }

        $this->command?->line(
            '  ✓ budgets seeded (' . $inserted . ' categories, ' . $overBudget . ' over plan)'
        );
    }

    protected function seedRecurringExpenses(): void
    {
        $months = ['2026-02', '2026-03', '2026-04', '2026-05'];
        $recurring = $this->profile()->recurringExpenses();

        $count = 0;
        foreach ($months as $month) {
            $monthCarbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            foreach ($recurring as [$cat, $title, $minMajor, $maxMajor]) {
                $amountMajor = mt_rand($minMajor, $maxMajor);
                $this->insertExpense([
                    'category' => $cat,
                    'title' => "{$title} ({$month})",
                    'description' => null,
                    'amount_paisas' => $amountMajor * 100,
                    'expense_date' => $monthCarbon->copy()->day(mt_rand(5, 25))->toDateString(),
                    'payment_method' => $this->profile()->weightedPick($this->profile()->paymentMethods()),
                    'reference_number' => 'INV-' . strtoupper(Str::random(8)),
                ]);
                $count++;
            }
        }
        $this->command?->line("  ✓ recurring expenses seeded ({$count})");
    }

    protected function seedPeriodicExpenses(): void
    {
        $periodic = $this->profile()->periodicExpenses();

        foreach ($periodic as [$cat, $title, $minMajor, $maxMajor, $date]) {
            $amount = mt_rand($minMajor, $maxMajor) * 100;
            $this->insertExpense([
                'category' => $cat,
                'title' => $title,
                'description' => null,
                'amount_paisas' => $amount,
                'expense_date' => $date,
                'payment_method' => $this->profile()->weightedPick($this->profile()->paymentMethods()),
                'reference_number' => 'EXP-' . strtoupper(Str::random(8)),
            ]);
        }
        $this->command?->line('  ✓ periodic expenses seeded (' . count($periodic) . ')');
    }

    /**
     * Per-staff per-month payroll record. Each generates one
     * 'Salaries' expense too.
     */
    protected function seedPayrolls(): void
    {
        DB::table('staff_payrolls')->delete();

        $staffProfiles = DB::table('staff_profiles')
            ->whereNull('deleted_at')
            ->get(['id', 'school_user_id', 'basic_salary_paisas']);
        $months = [
            ['2026-02', 2, 2026, 24],
            ['2026-03', 3, 2026, 26],
            ['2026-04', 4, 2026, 24],
            ['2026-05', 5, 2026, 22],
        ];
        $payrollCount = 0;
        $expenseCount = 0;
        foreach ($staffProfiles as $sp) {
            $basic = (int) $sp->basic_salary_paisas;
            $allowance = (int) round($basic * 0.30); // HRA + medical + conveyance lumped
            $deductions = (int) round($basic * 0.05); // PF
            $net = $basic + $allowance - $deductions;

            foreach ($months as [$monthStr, $monthNum, $year, $workingDays]) {
                $present = max(0, $workingDays - mt_rand(0, 3));
                $payrollId = (string) Str::ulid();
                DB::table('staff_payrolls')->insert([
                    'id' => $payrollId,
                    'staff_profile_id' => $sp->id,
                    'school_user_id' => $sp->school_user_id,
                    'month' => $monthNum,
                    'year' => $year,
                    'working_days' => $workingDays,
                    'present_days' => $present,
                    'basic_salary_paisas' => $basic,
                    'allowances_paisas' => $allowance,
                    'deductions_paisas' => $deductions,
                    'net_salary_paisas' => $net,
                    'status' => $monthNum === 5 ? 'pending' : 'paid',
                    'paid_at' => $monthNum === 5
                        ? null
                        : Carbon::create($year, $monthNum, mt_rand(1, 5))->endOfMonth(),
                    'processed_by' => $this->accountantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $payrollCount++;

                $this->insertExpense([
                    'category' => 'Salaries',
                    'title' => "Salary payout — staff #{$sp->id} ({$monthStr})",
                    'description' => "Net salary {$net} paisas",
                    'amount_paisas' => $net,
                    'expense_date' => Carbon::create($year, $monthNum, 5)->toDateString(),
                    'payment_method' => 'bank_transfer',
                    'reference_number' => 'PYR-' . strtoupper(Str::random(8)),
                    'forced_status' => $monthNum === 5 ? 'pending' : 'approved',
                ]);
                $expenseCount++;
            }
        }
        $this->command?->line("  ✓ staff_payrolls ({$payrollCount}), salary expenses ({$expenseCount})");
    }

    /**
     * @param  array{category:string, title:string, description:?string, amount_paisas:int, expense_date:string, payment_method:string, reference_number:?string, forced_status?:string}  $attrs
     */
    protected function insertExpense(array $attrs): void
    {
        $catId = $this->catIdByName[$attrs['category']] ?? null;
        if (! $catId) {
            return;
        }

        $forced = $attrs['forced_status'] ?? null;
        if ($forced) {
            $status = $forced;
            $approvedBy = $status === 'approved' ? $this->headId : null;
        } else {
            $r = mt_rand(1, 100);
            $status = $r <= 80 ? 'approved' : ($r <= 95 ? 'pending' : 'rejected');
            $approvedBy = match ($status) {
                'approved' => $this->headId,
                'rejected' => $this->headId,
                default => null,
            };
        }

        DB::table('expenses')->insert([
            'id' => (string) Str::ulid(),
            'category_id' => $catId,
            'budget_id' => null,
            'title' => $attrs['title'],
            'description' => $attrs['description'],
            'amount_paisas' => $attrs['amount_paisas'],
            'expense_date' => $attrs['expense_date'],
            'payment_method' => $attrs['payment_method'],
            'reference_number' => $attrs['reference_number'],
            'receipt_path' => null,
            'recorded_by' => $this->accountantId,
            'approved_by' => $approvedBy,
            'approval_status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
