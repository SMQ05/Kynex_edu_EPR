<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\Pak;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * e. FeesSeeder
 *
 * fee_groups (4), fee_types (~10), fee_masters per (class, fee_type)
 * with class-tier pricing, monthly student_fees for Feb-May 2026,
 * fee_payments + fee_payment_items per realistic distribution
 * (70% on time, 15% late, 10% partial, 5% unpaid), 2-3 refund rows.
 */
class FeesSeeder extends Seeder
{
    public string $academicYearId = '';
    public string $mainCampusId = '';

    /** fee_type name => fee_types.id */
    public array $feeTypeIdByName = [];

    /** "<classNumber>-<feeTypeName>" => amount_paisas */
    public array $masterAmount = [];

    public string $accountantUserId = '';

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
        public StudentsAndParentsSeeder $studentsAndParents,
    ) {}

    public function run(): void
    {
        $this->academicYearId = $this->classes->academicYearId;
        $this->mainCampusId = (string) DB::table('campuses')
            ->where('is_main_campus', true)
            ->value('id');
        $this->accountantUserId = $this->staff->userIdByLabel['accountant']
            ?? $this->staff->userIdByLabel['admin']
            ?? throw new \RuntimeException('No accountant or admin user available to record payments.');

        $this->seedFeeGroupsAndTypes();
        $this->seedFeeMasters();
        $this->seedAdmissionFees();
        $this->seedMonthlyFeesAndPayments();
        // Refunds are NOT seeded: the tenant DB has a check constraint
        // `chk_payment_positive` on fee_payments.total_amount_paisas that
        // forbids negative amounts. Modeling refunds as negative payments
        // is therefore not possible without schema work. Flagged in the
        // final report as follow-up.
    }

    protected function seedFeeGroupsAndTypes(): void
    {
        DB::table('fee_payment_items')->delete();
        DB::table('fee_payments')->delete();
        DB::table('student_fees')->delete();
        DB::table('fee_masters')->delete();
        DB::table('fee_types')->delete();
        DB::table('fee_groups')->delete();

        $groups = [
            'Tuition' => 'Recurring monthly tuition fees',
            'One-time Fees' => 'Admission and one-time charges',
            'Exam Fees' => 'Per-exam charges',
            'Optional Services' => 'Transport, library, sports, etc.',
        ];
        $groupId = [];
        foreach ($groups as $name => $desc) {
            $id = (string) Str::ulid();
            DB::table('fee_groups')->insert([
                'id' => $id,
                'name' => $name,
                'description' => $desc,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $groupId[$name] = $id;
        }
        $this->command?->line('  ✓ fee_groups seeded (4)');

        $types = [
            ['Tuition Monthly', 'Tuition', true],
            ['Admission Fee', 'One-time Fees', false],
            ['Annual Charges', 'One-time Fees', false],
            ['Lab Fee', 'Optional Services', true],
            ['Library Fee', 'Optional Services', true],
            ['Sports Fee', 'Optional Services', true],
            ['Computer Lab', 'Optional Services', true],
            ['Transport', 'Optional Services', true],
            ['Exam Fee', 'Exam Fees', false],
            ['Stationery', 'One-time Fees', false],
        ];
        foreach ($types as [$name, $group, $recurring]) {
            $id = (string) Str::ulid();
            DB::table('fee_types')->insert([
                'id' => $id,
                'fee_group_id' => $groupId[$group],
                'name' => $name,
                'is_recurring' => $recurring,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->feeTypeIdByName[$name] = $id;
        }
        $this->command?->line('  ✓ fee_types seeded (' . count($types) . ')');
    }

    /**
     * Per-class amounts (in PKR — converted to paisas at insert).
     *
     * Tuition Monthly:   Class 1-3 = 2,500   Class 4-7 = 4,500   Class 8-10 = 6,500
     * Admission Fee:     5,000 / 8,000 / 12,000
     * Annual Charges:    3,000 / 5,000 / 8,000
     * Library Fee:       200 / 300 / 400
     * Sports Fee:        300 / 400 / 500
     * Lab Fee:           — / 500 / 1,200
     * Computer Lab:      — / 600 / 800
     * Transport:         1,500 (flat) for all classes
     * Exam Fee:          500 / 800 / 1,200
     * Stationery:        500 / 750 / 1,000
     */
    protected function seedFeeMasters(): void
    {
        $tier = function (int $class): string {
            return $class <= 3 ? 'low' : ($class <= 7 ? 'mid' : 'high');
        };
        $rates = [
            'Tuition Monthly' => ['low' => 2500, 'mid' => 4500, 'high' => 6500],
            'Admission Fee' => ['low' => 5000, 'mid' => 8000, 'high' => 12000],
            'Annual Charges' => ['low' => 3000, 'mid' => 5000, 'high' => 8000],
            'Library Fee' => ['low' => 200, 'mid' => 300, 'high' => 400],
            'Sports Fee' => ['low' => 300, 'mid' => 400, 'high' => 500],
            'Lab Fee' => ['low' => 0, 'mid' => 500, 'high' => 1200],
            'Computer Lab' => ['low' => 0, 'mid' => 600, 'high' => 800],
            'Transport' => ['low' => 1500, 'mid' => 1500, 'high' => 1500],
            'Exam Fee' => ['low' => 500, 'mid' => 800, 'high' => 1200],
            'Stationery' => ['low' => 500, 'mid' => 750, 'high' => 1000],
        ];

        $count = 0;
        for ($n = 1; $n <= 10; $n++) {
            $classId = $this->classes->classIdByNumber[$n];
            $t = $tier($n);
            foreach ($rates as $typeName => $tierRates) {
                $amount = $tierRates[$t];
                if ($amount <= 0) {
                    continue;
                }
                DB::table('fee_masters')->insert([
                    'id' => (string) Str::ulid(),
                    'class_id' => $classId,
                    'section_id' => null,
                    'fee_type_id' => $this->feeTypeIdByName[$typeName],
                    'academic_year_id' => $this->academicYearId,
                    'amount_paisas' => $amount * 100,
                    'due_day' => 10,
                    'campus_id' => $this->mainCampusId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->masterAmount["{$n}-{$typeName}"] = $amount * 100;
                $count++;
            }
        }
        $this->command?->line("  ✓ fee_masters seeded ({$count})");
    }

    /**
     * Admission fee recorded once at admission_date — all marked paid.
     */
    protected function seedAdmissionFees(): void
    {
        $count = 0;
        $paymentCount = 0;
        $paymentItemCount = 0;
        $receiptSeq = 1;

        foreach ($this->studentsAndParents->studentRows as $s) {
            $amount = $this->masterAmount["{$s['class_number']}-Admission Fee"] ?? null;
            if ($amount === null) {
                continue;
            }
            $admissionDate = (string) DB::table('students')->where('id', $s['id'])->value('admission_date');
            $studentFeeId = (string) Str::ulid();

            DB::table('student_fees')->insert([
                'id' => $studentFeeId,
                'student_id' => $s['id'],
                'fee_type_id' => $this->feeTypeIdByName['Admission Fee'],
                'academic_year_id' => $this->academicYearId,
                'due_date' => $admissionDate,
                'amount_paisas' => $amount,
                'paid_paisas' => $amount,
                'discount_paisas' => 0,
                'fine_paisas' => 0,
                'status' => 'paid',
                'campus_id' => $this->mainCampusId,
                'month' => Carbon::parse($admissionDate)->format('Y-m'),
                'remarks' => 'Admission fee at enrollment',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;

            $paymentId = (string) Str::ulid();
            DB::table('fee_payments')->insert([
                'id' => $paymentId,
                'student_id' => $s['id'],
                'receipt_number' => sprintf('RCP-2026-%05d', $receiptSeq++),
                'total_amount_paisas' => $amount,
                'payment_date' => $admissionDate,
                'payment_method' => Pak::weightedPick(Pak::PAYMENT_METHODS),
                'collected_by' => $this->accountantUserId,
                'notes' => 'Admission payment',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('fee_payment_items')->insert([
                'id' => (string) Str::ulid(),
                'payment_id' => $paymentId,
                'student_fee_id' => $studentFeeId,
                'amount_paisas' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paymentCount++;
            $paymentItemCount++;
        }

        $this->command?->line("  ✓ Admission fee invoices ({$count}), payments ({$paymentCount})");
    }

    /**
     * Monthly invoices Feb-May 2026 for tuition, library, sports, lab,
     * computer lab. Transport for ~40% of students. Distribution per spec.
     */
    protected function seedMonthlyFeesAndPayments(): void
    {
        $months = ['2026-02', '2026-03', '2026-04', '2026-05'];
        $recurring = ['Tuition Monthly', 'Library Fee', 'Sports Fee', 'Lab Fee', 'Computer Lab'];
        $receiptSeq = DB::table('fee_payments')->count() + 1;

        // Pre-decide which students take transport (~40%).
        $transportStudentIds = [];
        foreach ($this->studentsAndParents->studentRows as $s) {
            if (mt_rand(1, 100) <= 40) {
                $transportStudentIds[$s['id']] = true;
            }
        }

        $feeRows = 0;
        $paymentRows = 0;
        $itemRows = 0;

        foreach ($this->studentsAndParents->studentRows as $s) {
            // Decide this student's persistent payment posture.
            $r = mt_rand(1, 100);
            $posture = $r <= 70 ? 'on_time' : ($r <= 85 ? 'late' : ($r <= 95 ? 'partial' : 'unpaid'));

            foreach ($months as $month) {
                $monthCarbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $dueDate = $monthCarbon->copy()->day(10);

                $feesForMonth = [];
                foreach ($recurring as $typeName) {
                    $amount = $this->masterAmount["{$s['class_number']}-{$typeName}"] ?? 0;
                    if ($amount <= 0) {
                        continue;
                    }
                    $feesForMonth[$typeName] = $amount;
                }
                if (isset($transportStudentIds[$s['id']])) {
                    $feesForMonth['Transport'] = $this->masterAmount["{$s['class_number']}-Transport"]
                        ?? 1500 * 100;
                }

                if (empty($feesForMonth)) {
                    continue;
                }

                $studentFeeIds = [];
                foreach ($feesForMonth as $typeName => $amount) {
                    [$paid, $status, $payDate] = $this->resolvePosture($posture, $dueDate, $amount);
                    $studentFeeId = (string) Str::ulid();
                    DB::table('student_fees')->insert([
                        'id' => $studentFeeId,
                        'student_id' => $s['id'],
                        'fee_type_id' => $this->feeTypeIdByName[$typeName],
                        'academic_year_id' => $this->academicYearId,
                        'due_date' => $dueDate->toDateString(),
                        'amount_paisas' => $amount,
                        'paid_paisas' => $paid,
                        'discount_paisas' => 0,
                        'fine_paisas' => $status === 'paid' && $payDate && $payDate->greaterThan($dueDate) ? 200_00 : 0,
                        'status' => $status,
                        'campus_id' => $this->mainCampusId,
                        'month' => $month,
                        'remarks' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $studentFeeIds[$studentFeeId] = ['paid' => $paid, 'status' => $status, 'pay_date' => $payDate];
                    $feeRows++;
                }

                // Group all paid items for this student-month into one fee_payment.
                $totalPaid = 0;
                $payDate = null;
                $itemBuf = [];
                foreach ($studentFeeIds as $sfid => $info) {
                    if ($info['paid'] > 0 && $info['pay_date']) {
                        $totalPaid += $info['paid'];
                        $payDate = $payDate ? $payDate->max($info['pay_date']) : $info['pay_date'];
                        $itemBuf[$sfid] = $info['paid'];
                    }
                }
                if ($totalPaid > 0 && $payDate) {
                    $paymentId = (string) Str::ulid();
                    DB::table('fee_payments')->insert([
                        'id' => $paymentId,
                        'student_id' => $s['id'],
                        'receipt_number' => sprintf('RCP-2026-%05d', $receiptSeq++),
                        'total_amount_paisas' => $totalPaid,
                        'payment_date' => $payDate->toDateString(),
                        'payment_method' => Pak::weightedPick(Pak::PAYMENT_METHODS),
                        'collected_by' => $this->accountantUserId,
                        'notes' => "Monthly fees for {$month}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $paymentRows++;
                    foreach ($itemBuf as $sfid => $amt) {
                        DB::table('fee_payment_items')->insert([
                            'id' => (string) Str::ulid(),
                            'payment_id' => $paymentId,
                            'student_fee_id' => $sfid,
                            'amount_paisas' => $amt,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $itemRows++;
                    }
                }
            }
        }

        $this->command?->line("  ✓ Monthly student_fees ({$feeRows}), fee_payments ({$paymentRows}), payment_items ({$itemRows})");
    }

    /**
     * Resolve a posture into (paid_paisas, status, pay_date|null) for a
     * single fee row.
     *
     * @return array{0:int,1:string,2:?Carbon}
     */
    protected function resolvePosture(string $posture, Carbon $dueDate, int $amount): array
    {
        return match ($posture) {
            'on_time' => [$amount, 'paid', $dueDate->copy()->subDays(mt_rand(0, 5))],
            'late' => [$amount, 'paid', $dueDate->copy()->addDays(mt_rand(3, 14))],
            'partial' => [(int) round($amount * mt_rand(30, 70) / 100), 'partial', $dueDate->copy()->subDays(mt_rand(0, 10))],
            'unpaid' => [0, 'pending', null],
            default => [$amount, 'paid', $dueDate],
        };
    }

    /**
     * 3 refund records — modeled as fee_payments with negative amounts.
     */
    protected function seedRefunds(): void
    {
        $sample = collect($this->studentsAndParents->studentRows)->take(3);
        $receiptSeq = DB::table('fee_payments')->count() + 1;

        $reasons = [
            'Sibling discount adjustment',
            'Withdrawal refund — partial month tuition',
            'Duplicate payment correction',
        ];

        $i = 0;
        foreach ($sample as $s) {
            $amount = -($this->masterAmount["{$s['class_number']}-Tuition Monthly"] ?? 250_000) / 2;
            $id = (string) Str::ulid();
            DB::table('fee_payments')->insert([
                'id' => $id,
                'student_id' => $s['id'],
                'receipt_number' => sprintf('REF-2026-%05d', $receiptSeq++),
                'total_amount_paisas' => (int) $amount,
                'payment_date' => Carbon::create(2026, 4, mt_rand(5, 25))->toDateString(),
                'payment_method' => 'bank_transfer',
                'collected_by' => $this->accountantUserId,
                'notes' => 'REFUND: ' . $reasons[$i++ % count($reasons)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ refunds (3) recorded as negative fee_payments');
    }
}
