<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\Pak;
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
        $this->seedBudget();
        $this->seedRecurringExpenses();
        $this->seedPeriodicExpenses();
        $this->seedPayrolls();
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
        // budgets schema: minimal — id, category_id?, amount_paisas, period dates.
        // The tenant migrations don't expose schema in detail here, so we
        // skip if columns differ. Best-effort: insert a single annual row.
        $columns = collect(DB::select("
            SELECT column_name FROM information_schema.columns
            WHERE table_schema = current_schema() AND table_name = 'budgets'
        "))->pluck('column_name')->all();

        $row = [
            'id' => (string) Str::ulid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (in_array('name', $columns, true)) {
            $row['name'] = 'Academic Year 2025-2026 Operating Budget';
        }
        if (in_array('amount_paisas', $columns, true)) {
            $row['amount_paisas'] = 50_000_000_00; // 50,000,000 PKR
        } elseif (in_array('total_amount_paisas', $columns, true)) {
            $row['total_amount_paisas'] = 50_000_000_00;
        }
        if (in_array('start_date', $columns, true)) {
            $row['start_date'] = '2025-09-01';
        }
        if (in_array('end_date', $columns, true)) {
            $row['end_date'] = '2026-06-30';
        }
        if (in_array('period', $columns, true)) {
            $row['period'] = 'annual';
        }
        if (in_array('description', $columns, true)) {
            $row['description'] = 'Annual operating budget covering salaries, utilities, supplies and capex.';
        }
        if (in_array('category_id', $columns, true)) {
            // Schema enforces NOT NULL — pin the budget to the Salaries
            // category so it's an actual line-item, not a "general" pool.
            $row['category_id'] = $this->catIdByName['Salaries'] ?? array_values($this->catIdByName)[0] ?? null;
        }
        if (in_array('campus_id', $columns, true)) {
            $row['campus_id'] = (string) DB::table('campuses')
                ->where('is_main_campus', true)
                ->value('id');
        }
        if (in_array('academic_year_id', $columns, true)) {
            $row['academic_year_id'] = (string) DB::table('academic_years')->where('is_current', true)->value('id');
        }
        if (in_array('created_by', $columns, true)) {
            $row['created_by'] = $this->headId;
        }

        try {
            DB::table('budgets')->insert($row);
            $this->command?->line('  ✓ budgets row seeded (1)');
        } catch (\Throwable $e) {
            $this->command?->warn('  ⚠ budgets insert skipped: ' . $e->getMessage());
        }
    }

    protected function seedRecurringExpenses(): void
    {
        $months = ['2026-02', '2026-03', '2026-04', '2026-05'];
        $recurring = [
            ['Utilities', 'Electricity bill', 80_000, 100_000],
            ['Utilities', 'Water bill', 8_000, 12_000],
            ['Utilities', 'Sui Gas bill', 6_000, 10_000],
            ['Internet & IT', 'Fiber internet (PTCL)', 12_000, 14_000],
            ['Rent', 'Building rent (Main Campus)', 250_000, 250_000],
        ];

        $count = 0;
        foreach ($months as $month) {
            $monthCarbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            foreach ($recurring as [$cat, $title, $minPkr, $maxPkr]) {
                $amountPkr = mt_rand($minPkr, $maxPkr);
                $this->insertExpense([
                    'category' => $cat,
                    'title' => "{$title} ({$month})",
                    'description' => null,
                    'amount_paisas' => $amountPkr * 100,
                    'expense_date' => $monthCarbon->copy()->day(mt_rand(5, 25))->toDateString(),
                    'payment_method' => Pak::weightedPick(Pak::PAYMENT_METHODS),
                    'reference_number' => 'INV-' . strtoupper(Str::random(8)),
                ]);
                $count++;
            }
        }
        $this->command?->line("  ✓ recurring expenses seeded ({$count})");
    }

    protected function seedPeriodicExpenses(): void
    {
        $periodic = [
            ['Stationery', 'Notebooks and copies bulk purchase', 25_000, 45_000, '2026-02-15'],
            ['Stationery', 'Whiteboard markers refresh', 8_000, 12_000, '2026-04-04'],
            ['Stationery', 'Printer paper bulk', 15_000, 22_000, '2026-03-08'],
            ['Lab Supplies', 'Chemistry lab chemicals restock', 35_000, 55_000, '2026-03-12'],
            ['Lab Supplies', 'Biology lab specimens', 18_000, 28_000, '2026-04-05'],
            ['Sports Equipment', 'New cricket and football kit', 45_000, 75_000, '2026-02-20'],
            ['Library Books', 'Books for new academic year', 60_000, 90_000, '2026-02-08'],
            ['Repairs & Maintenance', 'Roof leak fix, Block C', 32_000, 48_000, '2026-03-22'],
            ['Repairs & Maintenance', 'AC servicing — staff room', 12_000, 18_000, '2026-04-12'],
            ['Professional Development', 'Teacher training workshop', 35_000, 50_000, '2026-04-18'],
            ['Exam Printing', 'First Term exam papers', 25_000, 40_000, '2026-02-05'],
            ['Exam Printing', 'Mid Term exam papers', 25_000, 40_000, '2026-04-10'],
        ];

        foreach ($periodic as [$cat, $title, $minPkr, $maxPkr, $date]) {
            $amount = mt_rand($minPkr, $maxPkr) * 100;
            $this->insertExpense([
                'category' => $cat,
                'title' => $title,
                'description' => null,
                'amount_paisas' => $amount,
                'expense_date' => $date,
                'payment_method' => Pak::weightedPick(Pak::PAYMENT_METHODS),
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
