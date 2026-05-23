<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\Pak;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * b. StaffSeeder
 *
 * Seeds 18 staff total: 2 preserved (admin, head — updated in place,
 * IDs stable) + 16 new (VP, accountant, 10 teachers, clerk, librarian,
 * gatekeeper, driver). Each staff_user gets a paired staff_profiles
 * row, departments + designations are seeded fresh, model_has_roles is
 * synced.
 *
 * Designation field is set explicitly per-user (e.g. 'Vice Principal'
 * for the REGISTRAR-role VP) so the UI shows the human title regardless
 * of the auth role.
 */
class StaffSeeder extends Seeder
{
    /** Stable IDs of existing rows we keep — see plan §3. */
    public const ADMIN_USER_ID = '01kqjbfxf4kz91newwvvw3vxnp'; // Qamar Abbas → renamed
    public const HEAD_USER_ID = '01kqjbh722esjneea2czw0xf18';  // Haji Yaseen → renamed

    /** @var array<string, string> filled in by run() — name => school_users.id */
    public array $userIdByLabel = [];

    /** @var array<string, string> filled in by run() — designation name => id */
    public array $designationIdByName = [];

    /** @var array<string, string> filled in by run() — department name => id */
    public array $departmentIdByName = [];

    /** @var array<string, int> role name => roles.id (Spatie) */
    public array $roleIdByName = [];

    public string $mainCampusId = '';

    public function run(): void
    {
        $appKey = (string) config('app.key');
        $this->mainCampusId = (string) DB::table('campuses')
            ->where('is_main_campus', true)
            ->value('id');

        $this->loadRoles();
        $this->seedDepartments();
        $this->seedDesignations();
        $this->seedSalaryComponents();
        $this->seedLeaveTypes();
        $this->updatePreservedUsers($appKey);
        $this->seedNewStaff($appKey);
    }

    protected function loadRoles(): void
    {
        foreach (DB::table('roles')->select('id', 'name')->get() as $r) {
            $this->roleIdByName[$r->name] = (int) $r->id;
        }
    }

    protected function seedDepartments(): void
    {
        DB::table('staff_profiles')->whereNotNull('department_id')->update(['department_id' => null]);
        DB::table('designations')->whereNotNull('department_id')->update(['department_id' => null]);
        DB::table('departments')->delete();

        $departments = [
            'Administration' => 'School-wide leadership and office operations',
            'Academics' => 'Teaching faculty across primary and secondary',
            'Finance' => 'Fees, accounting and payroll',
            'Support' => 'Non-academic operational staff',
            'Library & Resources' => 'Library and learning-resource management',
        ];

        foreach ($departments as $name => $desc) {
            $id = (string) Str::ulid();
            DB::table('departments')->insert([
                'id' => $id,
                'name' => $name,
                'description' => $desc,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->departmentIdByName[$name] = $id;
        }
        $this->command?->line('  ✓ departments seeded (' . count($departments) . ')');
    }

    protected function seedDesignations(): void
    {
        DB::table('staff_profiles')->whereNotNull('designation_id')->update(['designation_id' => null]);
        DB::table('designations')->delete();

        $rows = [
            'Principal' => 'Administration',
            'Vice Principal' => 'Administration',
            'Office Manager' => 'Administration',
            'Accountant' => 'Finance',
            'Senior Teacher' => 'Academics',
            'Teacher' => 'Academics',
            'Quran Teacher' => 'Academics',
            'Librarian' => 'Library & Resources',
            'Clerk' => 'Support',
            'Gatekeeper' => 'Support',
            'Driver' => 'Support',
        ];

        foreach ($rows as $name => $deptName) {
            $id = (string) Str::ulid();
            DB::table('designations')->insert([
                'id' => $id,
                'name' => $name,
                'department_id' => $this->departmentIdByName[$deptName] ?? null,
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->designationIdByName[$name] = $id;
        }
        $this->command?->line('  ✓ designations seeded (' . count($rows) . ')');
    }

    protected function seedSalaryComponents(): void
    {
        DB::table('salary_components')->delete();
        $components = [
            ['Basic Salary', 'earning', 'fixed', 0, false],
            ['House Rent Allowance', 'allowance', 'percentage', 30, false],
            ['Conveyance Allowance', 'allowance', 'fixed', 300_000, false], // 3,000 PKR in paisas
            ['Medical Allowance', 'allowance', 'fixed', 200_000, false],
            ['Provident Fund', 'deduction', 'percentage', 5, false],
        ];
        foreach ($components as [$name, $type, $calc, $val, $tax]) {
            DB::table('salary_components')->insert([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'component_type' => $type,
                'calculation_type' => $calc,
                'default_value_paisas' => $val,
                'is_taxable' => $tax,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ salary_components seeded (5)');
    }

    protected function seedLeaveTypes(): void
    {
        DB::table('leave_types')->delete();
        $rows = [
            ['Casual Leave', 'staff', 12, true],
            ['Sick Leave', 'staff', 10, true],
            ['Annual Leave', 'staff', 18, true],
            ['Maternity Leave', 'staff', 90, true],
            ['Unpaid Leave', 'staff', 30, false],
        ];
        foreach ($rows as [$name, $applicable, $maxDays, $paid]) {
            DB::table('leave_types')->insert([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'applicable_to' => $applicable,
                'max_days_per_year' => $maxDays,
                'is_paid' => $paid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ leave_types seeded (5)');
    }

    /**
     * Preserved users: rename Qamar Abbas → keep ID, set new display name &
     * email; rename Haji Yaseen → Principal AQM. Both get fresh demo
     * passwords, fresh staff_profiles, and proper role + designation links.
     */
    protected function updatePreservedUsers(string $appKey): void
    {
        // ── Admin / Office Manager ───────────────────────────────────
        $adminEmail = 'admin@aqmdigital.com';
        DB::table('school_users')->where('id', self::ADMIN_USER_ID)->update([
            'name' => 'Qamar Abbas',
            'email' => $adminEmail,
            'phone' => Pak::phone(),
            'whatsapp' => Pak::phone(),
            'password' => Hash::make(Pak::demoPassword('admin', $adminEmail, $appKey)),
            'is_active' => true,
            'active_role' => 'SCHOOL_ADMIN',
            'campus_id' => $this->mainCampusId,
            'email_verified_at' => now(),
            'updated_at' => now(),
        ]);
        $this->upsertStaffProfile(self::ADMIN_USER_ID, [
            'employee_id' => 'EMP-001',
            'designation_name' => 'Office Manager',
            'qualification' => 'MBA (Administration)',
            'joining_year' => 2018,
            'salary_paisas' => 90_000_00,
        ]);
        $this->syncSingleRole(self::ADMIN_USER_ID, 'SCHOOL_ADMIN');
        $this->userIdByLabel['admin'] = self::ADMIN_USER_ID;
        $this->command?->line('  ✓ Preserved admin updated → ' . $adminEmail);

        // ── Principal (Head) ─────────────────────────────────────────
        $principalEmail = 'principal@aqmdigital.com';
        DB::table('school_users')->where('id', self::HEAD_USER_ID)->update([
            'name' => 'Khalid Mahmood',
            'email' => $principalEmail,
            'phone' => Pak::phone(),
            'whatsapp' => Pak::phone(),
            'password' => Hash::make(Pak::demoPassword('principal', $principalEmail, $appKey)),
            'is_active' => true,
            'active_role' => 'INSTITUTE_HEAD',
            'campus_id' => $this->mainCampusId,
            'email_verified_at' => now(),
            'updated_at' => now(),
        ]);
        $this->upsertStaffProfile(self::HEAD_USER_ID, [
            'employee_id' => 'EMP-002',
            'designation_name' => 'Principal',
            'qualification' => 'MA Education, M.Phil',
            'joining_year' => 2018,
            'salary_paisas' => 150_000_00,
        ]);
        $this->syncSingleRole(self::HEAD_USER_ID, 'INSTITUTE_HEAD');
        $this->userIdByLabel['principal'] = self::HEAD_USER_ID;
        $this->command?->line('  ✓ Preserved head updated → ' . $principalEmail);
    }

    protected function seedNewStaff(string $appKey): void
    {
        $issued = ['admin@aqmdigital.com' => true, 'principal@aqmdigital.com' => true];
        $empSeq = 3; // EMP-001 admin, EMP-002 principal already taken

        $newStaff = [
            // [authRole, designation, label, qualification, salaryPaisas, hardName]
            ['REGISTRAR', 'Vice Principal', 'vice-principal', 'MSc Education, B.Ed', 110_000_00, 'Saima Naveed'],
            ['ACCOUNTANT', 'Accountant', 'accountant', 'B.Com, ACCA Part-Qualified', 70_000_00, 'Imran Sheikh'],

            ['TEACHER', 'Senior Teacher', 'teacher_math', 'MSc Mathematics, B.Ed', 65_000_00, 'Naveed Ahmed', 'Math'],
            ['TEACHER', 'Senior Teacher', 'teacher_english', 'MA English, B.Ed', 62_000_00, 'Sadia Khan', 'English'],
            ['TEACHER', 'Teacher', 'teacher_urdu', 'MA Urdu, B.Ed', 55_000_00, 'Bushra Iqbal', 'Urdu'],
            ['TEACHER', 'Teacher', 'teacher_science', 'MSc Biology, B.Ed', 58_000_00, 'Asad Mahmood', 'Science'],
            ['TEACHER', 'Teacher', 'teacher_social', 'MA History, B.Ed', 52_000_00, 'Tariq Hussain', 'Social Studies'],
            ['TEACHER', 'Teacher', 'teacher_islamiyat', 'MA Islamic Studies', 50_000_00, 'Hafiz Bilal', 'Islamiyat'],
            ['TEACHER', 'Teacher', 'teacher_computer', 'BS Computer Science', 60_000_00, 'Hamza Aziz', 'Computer'],
            ['TEACHER', 'Teacher', 'teacher_arts', 'BFA Fine Arts', 48_000_00, 'Mahnoor Riaz', 'Arts'],
            ['TEACHER', 'Teacher', 'teacher_pe', 'BSc Sports Sciences', 50_000_00, 'Faisal Akram', 'Physical Education'],
            ['TEACHER', 'Quran Teacher', 'teacher_quran', 'Hafiz-e-Quran, MA Islamic Studies', 50_000_00, 'Qari Owais', 'Quran'],

            ['ATTENDANCE_CLERK', 'Clerk', 'clerk', 'BA, IT Diploma', 35_000_00, 'Salman Tariq'],
            ['LIBRARIAN', 'Librarian', 'librarian', 'BLIS (Library & Information Sciences)', 40_000_00, 'Aqsa Saeed'],
            // Gatekeeper and driver have NO auth role (no portal access).
            [null, 'Gatekeeper', 'gatekeeper', 'Matric', 28_000_00, 'Akram Hussain'],
            [null, 'Driver', 'driver', 'Matric, LTV License', 30_000_00, 'Babar Iqbal'],
        ];

        foreach ($newStaff as $row) {
            [$authRole, $designation, $label, $qualification, $salaryPaisas, $name] = array_pad($row, 6, null);
            $subject = $row[6] ?? null; // teacher's subject domain — only TEACHER rows have it

            [$first, $last] = $this->splitName($name);
            $emailHandle = Pak::emailHandle($first, $last);
            $email = Pak::uniqueEmail($emailHandle, $issued);

            $userId = (string) Str::ulid();
            $passwordKey = Pak::roleKeyFor((string) ($authRole ?? 'STAFF'));
            DB::table('school_users')->insert([
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Pak::demoPassword($passwordKey, $email, $appKey)),
                'phone' => Pak::phone(),
                'whatsapp' => Pak::phone(),
                'is_active' => true,
                'active_role' => $authRole,
                'campus_id' => $this->mainCampusId,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->upsertStaffProfile($userId, [
                'employee_id' => sprintf('EMP-%03d', $empSeq++),
                'designation_name' => $designation,
                'qualification' => $qualification,
                'joining_year' => 2018 + mt_rand(0, 7),
                'salary_paisas' => $salaryPaisas,
            ]);

            if ($authRole) {
                $this->syncSingleRole($userId, $authRole);
            }

            $this->userIdByLabel[$label] = $userId;
            if ($subject) {
                $this->userIdByLabel['subject:' . $subject] = $userId;
            }
        }

        $this->command?->line('  ✓ ' . count($newStaff) . ' new staff seeded');
    }

    /**
     * @param  array{employee_id:string, designation_name:string, qualification:string, joining_year:int, salary_paisas:int}  $attrs
     */
    protected function upsertStaffProfile(string $userId, array $attrs): void
    {
        $department = $this->designationToDepartment($attrs['designation_name']);
        $departmentId = $this->departmentIdByName[$department] ?? null;
        $designationId = $this->designationIdByName[$attrs['designation_name']] ?? null;
        $joinDate = Carbon::create($attrs['joining_year'], mt_rand(1, 12), mt_rand(1, 28));
        $experience = max(0, Carbon::now()->year - $attrs['joining_year']);

        $existing = DB::table('staff_profiles')->where('school_user_id', $userId)->first();
        $payload = [
            'school_user_id' => $userId,
            'employee_id' => $attrs['employee_id'],
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'joining_date' => $joinDate->toDateString(),
            'employment_type' => 'permanent',
            'qualification' => $attrs['qualification'],
            'experience_years' => $experience,
            'emergency_contact_name' => Pak::pick(Pak::SURNAMES) . ' ' . Pak::pick(Pak::MALE_FIRST_NAMES),
            'emergency_contact_phone' => Pak::phone(),
            'bank_name' => Pak::pick(['HBL', 'UBL', 'MCB', 'Allied Bank', 'Meezan Bank', 'Bank Alfalah']),
            'bank_account' => 'PK' . str_pad((string) mt_rand(0, 99), 2, '0', STR_PAD_LEFT) . str_pad((string) mt_rand(1, 9_999_999), 16, '0', STR_PAD_LEFT),
            'basic_salary_paisas' => $attrs['salary_paisas'],
            'campus_id' => $this->mainCampusId,
            'personal_whatsapp' => Pak::phone(),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('staff_profiles')->where('id', $existing->id)->update($payload);
        } else {
            $payload['id'] = (string) Str::ulid();
            $payload['created_at'] = now();
            DB::table('staff_profiles')->insert($payload);
        }
    }

    protected function designationToDepartment(string $designation): string
    {
        return match ($designation) {
            'Principal', 'Vice Principal', 'Office Manager' => 'Administration',
            'Accountant' => 'Finance',
            'Senior Teacher', 'Teacher', 'Quran Teacher' => 'Academics',
            'Librarian' => 'Library & Resources',
            default => 'Support',
        };
    }

    /**
     * Replace any existing model_has_roles for $userId with one row pointing
     * at $roleName. Spatie uses (model_id, model_type, role_id) so we delete
     * matching tuples then insert the canonical one.
     *
     * Public so peer seeders (Students, etc.) can sync STUDENT/PARENT roles
     * for the school_users they create.
     */
    public function syncSingleRole(string $userId, string $roleName): void
    {
        $roleId = $this->roleIdByName[$roleName] ?? null;
        if (! $roleId) {
            $this->command?->warn("    ⚠ Role '{$roleName}' not found in roles table");
            return;
        }

        DB::table('model_has_roles')
            ->where('model_id', $userId)
            ->delete();

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => \App\Models\SchoolUser::class,
            'model_id' => $userId,
        ]);
    }

    protected function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], 'Khan'];
        }
        $first = (string) array_shift($parts);
        $last = implode(' ', $parts);
        return [$first, $last];
    }
}
