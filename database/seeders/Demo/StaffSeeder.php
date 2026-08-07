<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;
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
    use UsesDemoProfile;

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

        $rows = $this->profile()->designations();

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
        $components = $this->profile()->salaryComponents();
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
     * Seed the two standing leadership accounts (admin + head of school).
     *
     * Originally this method did a bare UPDATE on two hardcoded ULIDs
     * (ADMIN_USER_ID / HEAD_USER_ID) that exist only in the live AQM tenant.
     * On any other tenant that UPDATE matched zero rows, so the demo silently
     * came up with no admin and no principal at all — the two accounts you
     * most need in order to log in and look at it.
     *
     * It now upserts: reuse the preserved ULID where it genuinely exists
     * (so AQM keeps its stable IDs and history), otherwise fall back to an
     * existing row with the same email, otherwise insert a fresh row.
     */
    protected function updatePreservedUsers(string $appKey): void
    {
        $leadership = $this->profile()->leadership();
        $domain = $this->profile()->emailDomain();

        $this->upsertLeader(
            preservedId: self::ADMIN_USER_ID,
            email: 'admin@' . $domain,
            authRole: 'SCHOOL_ADMIN',
            passwordKey: 'admin',
            label: 'admin',
            meta: $leadership['admin'],
            appKey: $appKey,
        );

        $this->upsertLeader(
            preservedId: self::HEAD_USER_ID,
            email: 'principal@' . $domain,
            authRole: 'INSTITUTE_HEAD',
            passwordKey: 'principal',
            label: 'principal',
            meta: $leadership['principal'],
            appKey: $appKey,
        );
    }

    /**
     * Create-or-update one leadership account and its staff profile.
     *
     * @param  array{name:string,designation:string,qualification:string,employee_id:string,salary:int}  $meta
     */
    protected function upsertLeader(
        string $preservedId,
        string $email,
        string $authRole,
        string $passwordKey,
        string $label,
        array $meta,
        string $appKey,
    ): void {
        // Prefer the preserved ULID, then any row already using this email,
        // then mint a new id.
        $userId = DB::table('school_users')->where('id', $preservedId)->value('id')
            ?: DB::table('school_users')->where('email', $email)->value('id')
            ?: (string) Str::ulid();

        $attributes = [
            'name' => $meta['name'],
            'email' => $email,
            'phone' => $this->profile()->phone(),
            'whatsapp' => $this->profile()->phone(),
            'password' => Hash::make($this->profile()->demoPassword($passwordKey, $email, $appKey)),
            'is_active' => true,
            'active_role' => $authRole,
            'campus_id' => $this->mainCampusId,
            'email_verified_at' => now(),
            'updated_at' => now(),
        ];

        $exists = DB::table('school_users')->where('id', $userId)->exists();
        if ($exists) {
            DB::table('school_users')->where('id', $userId)->update($attributes);
            $verb = 'updated';
        } else {
            DB::table('school_users')->insert($attributes + [
                'id' => $userId,
                'created_at' => now(),
            ]);
            $verb = 'created';
        }

        $this->upsertStaffProfile($userId, [
            'employee_id' => $meta['employee_id'],
            'designation_name' => $meta['designation'],
            'qualification' => $meta['qualification'],
            'joining_year' => 2018,
            'salary_paisas' => $meta['salary'],
        ]);
        $this->syncSingleRole($userId, $authRole);
        $this->userIdByLabel[$label] = $userId;

        $this->command?->line("  ✓ {$meta['designation']} {$verb} → {$email}");
    }

    protected function seedNewStaff(string $appKey): void
    {
        $domain = $this->profile()->emailDomain();
        $issued = ['admin@' . $domain => true, 'principal@' . $domain => true];
        $empSeq = 3; // EMP-001 admin, EMP-002 principal already taken

        $newStaff = $this->profile()->staffRoster();

        foreach ($newStaff as $row) {
            [$authRole, $designation, $label, $qualification, $salaryPaisas, $name] = array_pad($row, 6, null);
            $subject = $row[6] ?? null; // teacher's subject domain — only TEACHER rows have it

            [$first, $last] = $this->splitName($name);
            $emailHandle = $this->profile()->emailHandle($first, $last);
            $email = $this->profile()->uniqueEmail($emailHandle, $issued);

            $userId = (string) Str::ulid();
            $passwordKey = $this->profile()->roleKeyFor((string) ($authRole ?? 'STAFF'));
            DB::table('school_users')->insert([
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($this->profile()->demoPassword($passwordKey, $email, $appKey)),
                'phone' => $this->profile()->phone(),
                'whatsapp' => $this->profile()->phone(),
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
            'emergency_contact_name' => $this->profile()->pick($this->profile()->surnames()) . ' ' . $this->profile()->pick($this->profile()->maleFirstNames()),
            'emergency_contact_phone' => $this->profile()->phone(),
            'bank_name' => $this->profile()->pick($this->profile()->banks()),
            'bank_account' => $this->profile()->bankAccountNumber(),
            'basic_salary_paisas' => $attrs['salary_paisas'],
            'campus_id' => $this->mainCampusId,
            'personal_whatsapp' => $this->profile()->phone(),
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
