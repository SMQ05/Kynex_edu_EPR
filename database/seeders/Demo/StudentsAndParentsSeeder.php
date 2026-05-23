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
 * d. StudentsAndParentsSeeder
 *
 * Seeds 100 active students + 5 alumni (used as FK targets for completion
 * certificates). Each active student gets a paired school_users row with
 * STUDENT role; each unique parent identity gets a paired school_users
 * row with PARENT role; siblings share parent rows.
 *
 * Class size distribution per spec: [12, 11, 10, 10, 9, 10, 10, 9, 10, 9].
 * For classes 1-5, students split between sections A and B.
 */
class StudentsAndParentsSeeder extends Seeder
{
    public const CLASS_SIZES = [
        1 => 12, 2 => 11, 3 => 10, 4 => 10, 5 => 9,
        6 => 10, 7 => 10, 8 => 9, 9 => 10, 10 => 9,
    ];

    public string $academicYearId = '';
    public string $mainCampusId = '';

    /** @var array<string, string> studentLabel => students.id (e.g. "AQM-2025-001" => ULID) */
    public array $studentIdByAdmission = [];

    /** @var list<array{id:string, class_id:string, section_id:string, class_number:int, school_user_id:?string, name:string}> */
    public array $studentRows = [];

    /** @var list<array{id:string, name:string, gender:string, email:string}> */
    public array $parentRows = [];

    /** @var list<string> */
    public array $alumniIds = [];

    public function __construct(
        public StaffSeeder $staff,
        public ClassesSeeder $classes,
    ) {}

    public function run(): void
    {
        $appKey = (string) config('app.key');
        $this->mainCampusId = (string) DB::table('campuses')
            ->where('is_main_campus', true)
            ->value('id');
        $this->academicYearId = $this->classes->academicYearId;

        $this->seedStudentCategories();
        $this->seedStudentsAndParents($appKey);
        $this->seedAlumni();
    }

    protected function seedStudentCategories(): void
    {
        DB::table('student_categories')->delete();
        $rows = [
            ['General', '#3b82f6'],
            ['Sibling Discount', '#22c55e'],
            ['Staff Child', '#a855f7'],
        ];
        foreach ($rows as [$name, $color]) {
            DB::table('student_categories')->insert([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'description' => null,
                'color' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ student_categories seeded (3)');
    }

    /**
     * Strategy: walk class-by-class, generate students with realistic
     * roll numbers per class. ~25% of students share a parent set with
     * a previously-generated student to simulate siblings (same surname,
     * same address, same parent rows linked).
     */
    protected function seedStudentsAndParents(string $appKey): void
    {
        $issuedEmails = [
            'admin@aqmdigital.com' => true,
            'principal@aqmdigital.com' => true,
        ];
        // Re-add staff emails to avoid collisions
        foreach (DB::table('school_users')->pluck('email') as $email) {
            $issuedEmails[$email] = true;
        }

        // Parent pool: keyed by family-id "<lastName>-<index>". Each entry
        // holds {father:?array, mother:?array, address:string, city:string}.
        $families = [];
        $admissionSeq = 1;
        $studentTotal = 0;

        foreach (self::CLASS_SIZES as $classNumber => $size) {
            $classId = $this->classes->classIdByNumber[$classNumber];
            $sectionsForClass = $classNumber <= 5 ? ['A', 'B'] : ['A'];
            $rollSeqBySection = [];
            foreach ($sectionsForClass as $sec) {
                $rollSeqBySection[$sec] = 1;
            }

            for ($i = 0; $i < $size; $i++) {
                $sectionName = $sectionsForClass[$i % count($sectionsForClass)];
                $sectionId = $this->classes->sectionByKey["{$classNumber}-{$sectionName}"]['id']
                    ?? throw new \RuntimeException("Section {$classNumber}-{$sectionName} missing");

                // Decide sibling vs new family. ~25% sibling rate but only
                // when a candidate family exists with the right surname diversity.
                $useSibling = (count($families) > 5) && (mt_rand(1, 100) <= 25);
                if ($useSibling) {
                    $familyKey = (string) array_rand($families);
                    $family = $families[$familyKey];
                    $surname = $family['surname'];
                    $address = $family['address'];
                    $city = $family['city'];
                } else {
                    $surname = Pak::pick(Pak::SURNAMES);
                    $addr = Pak::address();
                    $address = $addr['address'];
                    $city = $addr['city'];
                    $family = null;
                    $familyKey = null;
                }

                // Gender distribution ~52% female / 48% male, with some noise.
                $isFemale = mt_rand(1, 100) <= 52;
                $firstName = Pak::pick($isFemale ? Pak::FEMALE_FIRST_NAMES : Pak::MALE_FIRST_NAMES);
                $name = $firstName . ' ' . $surname;

                $studentId = (string) Str::ulid();
                $admissionNumber = sprintf('AQM-2025-%03d', $admissionSeq++);
                $rollNumber = (string) ($rollSeqBySection[$sectionName]++);

                // DOB based on class level: Class 1 ≈ 5-6 → DOB 2019-2020, Class 10 ≈ 14-15 → 2010-2011.
                $age = 4 + $classNumber + mt_rand(0, 1);
                $dob = Carbon::now()->subYears($age)->subDays(mt_rand(0, 365));

                $admissionDate = Carbon::create(2020 + min(5, max(1, 6 - $classNumber + mt_rand(0, 2))), mt_rand(3, 8), mt_rand(1, 28));
                if ($admissionDate->greaterThan(Carbon::now())) {
                    $admissionDate = Carbon::now()->subDays(mt_rand(30, 365));
                }

                $studentEmail = Pak::uniqueEmail(
                    Pak::emailHandle($firstName, $admissionNumber),
                    $issuedEmails,
                );
                $userId = (string) Str::ulid();

                // Insert paired school_user (role STUDENT).
                DB::table('school_users')->insert([
                    'id' => $userId,
                    'name' => $name,
                    'email' => $studentEmail,
                    'password' => Hash::make(Pak::demoPassword('student', $studentEmail, $appKey)),
                    'phone' => Pak::phone(),
                    'is_active' => true,
                    'active_role' => 'STUDENT',
                    'campus_id' => $this->mainCampusId,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->staff->syncSingleRole($userId, 'STUDENT');

                DB::table('students')->insert([
                    'id' => $studentId,
                    'admission_number' => $admissionNumber,
                    'roll_number' => $rollNumber,
                    'admission_date' => $admissionDate->toDateString(),
                    'academic_year_id' => $this->academicYearId,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'category_id' => null,
                    'campus_id' => $this->mainCampusId,
                    'first_name' => $firstName,
                    'last_name' => $surname,
                    'date_of_birth' => $dob->toDateString(),
                    'gender' => $isFemale ? 'female' : 'male',
                    'blood_group' => Pak::weightedPick(Pak::BLOOD_GROUP_WEIGHTS),
                    'religion' => 'Islam',
                    'nationality' => 'Pakistani',
                    'profile_photo_path' => 'https://placehold.co/200x200/4f46e5/ffffff?text=' . urlencode(strtoupper(substr($firstName, 0, 1) . substr($surname, 0, 1))),
                    'phone' => Pak::phone(),
                    'email' => $studentEmail,
                    'address' => $address,
                    'city' => $city,
                    'status' => 'enrolled',
                    'school_user_id' => $userId,
                    'previous_school' => null,
                    'registration_number' => 'REG-' . strtoupper(Str::random(6)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->studentRows[] = [
                    'id' => $studentId,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'class_number' => $classNumber,
                    'school_user_id' => $userId,
                    'name' => $name,
                    'admission_number' => $admissionNumber,
                ];
                $this->studentIdByAdmission[$admissionNumber] = $studentId;
                $studentTotal++;

                $family = $this->ensureParentsForFamily(
                    $family,
                    $surname,
                    $address,
                    $city,
                    $appKey,
                    $issuedEmails,
                );
                if ($familyKey === null) {
                    $familyKey = $surname . '-' . count($families);
                    $families[$familyKey] = $family;
                } else {
                    $families[$familyKey] = $family;
                }

                $this->linkParentsToStudent($family, $studentId);
            }
        }

        $this->command?->line("  ✓ students seeded ({$studentTotal})");
        $this->command?->line('  ✓ parents (school_users role=PARENT) seeded (' . count($this->parentRows) . ')');
        $this->command?->line('  ✓ student_guardians rows: ' . DB::table('student_guardians')->count());
    }

    /**
     * Ensure the family has at least one parent (father preferred).
     * Returns the (possibly mutated) family record.
     *
     * @param  array{father:?array, mother:?array, surname:string, address:string, city:string}|null  $family
     * @param  array<string, bool>  $issuedEmails
     */
    protected function ensureParentsForFamily(
        ?array $family,
        string $surname,
        string $address,
        string $city,
        string $appKey,
        array &$issuedEmails,
    ): array {
        if ($family && $family['father']) {
            return $family;
        }
        $family = $family ?? [
            'father' => null,
            'mother' => null,
            'surname' => $surname,
            'address' => $address,
            'city' => $city,
        ];

        // Always create a father (most demos expect a primary contact).
        $fatherFirst = Pak::pick(Pak::MALE_FIRST_NAMES);
        $father = $this->createParentSchoolUser(
            $fatherFirst . ' ' . $surname,
            'male',
            $appKey,
            $issuedEmails,
        );
        $family['father'] = $father;

        // 70% chance to also create a mother as a portal user, 30% the
        // mother exists as a guardian-only row (no school_user, no portal).
        if (mt_rand(1, 100) <= 70) {
            $motherFirst = Pak::pick(Pak::FEMALE_FIRST_NAMES);
            $mother = $this->createParentSchoolUser(
                $motherFirst . ' ' . $surname,
                'female',
                $appKey,
                $issuedEmails,
            );
            $family['mother'] = $mother;
        }

        return $family;
    }

    /**
     * Insert a school_users row for a parent and return its handle.
     *
     * @return array{user_id:string, name:string, gender:string, email:string, phone:string, cnic:string, occupation:string}
     */
    protected function createParentSchoolUser(string $name, string $gender, string $appKey, array &$issuedEmails): array
    {
        [$first, $last] = explode(' ', $name, 2);
        $email = Pak::uniqueEmail(Pak::emailHandle($first, $last), $issuedEmails);
        $userId = (string) Str::ulid();
        $phone = Pak::phone();
        $cnic = Pak::cnic();
        $occupation = Pak::pick(Pak::PARENT_OCCUPATIONS);

        DB::table('school_users')->insert([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Pak::demoPassword('parent', $email, $appKey)),
            'phone' => $phone,
            'whatsapp' => $phone,
            'is_active' => true,
            'active_role' => 'PARENT',
            'campus_id' => $this->mainCampusId,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->staff->syncSingleRole($userId, 'PARENT');

        $row = compact('userId', 'name', 'gender', 'email', 'phone', 'cnic', 'occupation');
        $row['user_id'] = $userId;
        unset($row['userId']);
        $this->parentRows[] = $row;
        return $row;
    }

    /**
     * Insert student_guardians rows linking the family's father (and
     * optionally mother) to the given student.
     *
     * @param  array{father:array, mother:?array, address:string, city:string, surname:string}  $family
     */
    protected function linkParentsToStudent(array $family, string $studentId): void
    {
        $father = $family['father'];
        DB::table('student_guardians')->insert([
            'id' => (string) Str::ulid(),
            'student_id' => $studentId,
            'guardian_type' => 'parent',
            'name' => $father['name'],
            'relationship' => 'father',
            'phone' => $father['phone'],
            'whatsapp' => $father['phone'],
            'email' => $father['email'],
            'occupation' => $father['occupation'],
            'address' => $family['address'],
            'cnic' => $father['cnic'],
            'is_primary_contact' => true,
            'can_access_portal' => true,
            'school_user_id' => $father['user_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($family['mother']) {
            $mother = $family['mother'];
            DB::table('student_guardians')->insert([
                'id' => (string) Str::ulid(),
                'student_id' => $studentId,
                'guardian_type' => 'parent',
                'name' => $mother['name'],
                'relationship' => 'mother',
                'phone' => $mother['phone'],
                'whatsapp' => $mother['phone'],
                'email' => $mother['email'],
                'occupation' => $mother['occupation'],
                'address' => $family['address'],
                'cnic' => $mother['cnic'],
                'is_primary_contact' => false,
                'can_access_portal' => true,
                'school_user_id' => $mother['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * 5 alumni records (status=graduated, deleted_at=null) used as FK
     * targets for completion certificates. No login row, no guardians.
     */
    protected function seedAlumni(): void
    {
        $class10Id = $this->classes->classIdByNumber[10];
        $section10A = $this->classes->sectionByKey['10-A']['id'] ?? null;

        $alumniSeq = 1;
        $alumni = [
            ['Imran', 'Khan', 'male', 2024],
            ['Fatima', 'Sheikh', 'female', 2024],
            ['Hassan', 'Iqbal', 'male', 2025],
            ['Maryam', 'Ahmed', 'female', 2025],
            ['Ali', 'Hussain', 'male', 2025],
        ];

        foreach ($alumni as [$first, $last, $gender, $gradYear]) {
            $id = (string) Str::ulid();
            $admissionNumber = sprintf('AQM-ALM-%03d', $alumniSeq++);
            $admissionDate = Carbon::create($gradYear - 10, mt_rand(3, 8), mt_rand(1, 28));
            $dob = Carbon::create($gradYear - 16, mt_rand(1, 12), mt_rand(1, 28));

            DB::table('students')->insert([
                'id' => $id,
                'admission_number' => $admissionNumber,
                'roll_number' => null,
                'admission_date' => $admissionDate->toDateString(),
                'academic_year_id' => $this->academicYearId,
                'class_id' => $class10Id,
                'section_id' => $section10A,
                'category_id' => null,
                'campus_id' => $this->mainCampusId,
                'first_name' => $first,
                'last_name' => $last,
                'date_of_birth' => $dob->toDateString(),
                'gender' => $gender,
                'blood_group' => Pak::weightedPick(Pak::BLOOD_GROUP_WEIGHTS),
                'religion' => 'Islam',
                'nationality' => 'Pakistani',
                'address' => Pak::address()['address'],
                'city' => Pak::address()['city'],
                'status' => 'graduated',
                'status_changed_at' => Carbon::create($gradYear, 6, 30),
                'school_user_id' => null,
                'registration_number' => 'REG-' . strtoupper(Str::random(6)),
                'created_at' => Carbon::create($gradYear - 10),
                'updated_at' => Carbon::create($gradYear, 6, 30),
            ]);
            $this->alumniIds[] = $id;
        }
        $this->command?->line('  ✓ alumni seeded (5)');
    }
}
