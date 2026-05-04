<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\ExamResultStatus;
use App\Enums\ExamStatus;
use App\Enums\FeePaymentMethod;
use App\Enums\StudentFeeStatus;
use App\Enums\StudentGender;
use App\Enums\StudentStatus;
use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Campus;
use App\Models\Tenant\ClassRoutine;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\CmsAnnouncement;
use App\Models\Tenant\CmsGalleryAlbum;
use App\Models\Tenant\CmsGalleryPhoto;
use App\Models\Tenant\CmsPage;
use App\Models\Tenant\CmsSetting;
use App\Models\Tenant\CmsSlider;
use App\Models\Tenant\Department;
use App\Models\Tenant\Designation;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\ExpenseCategory;
use App\Models\Tenant\FeeGroup;
use App\Models\Tenant\FeeMaster;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\FeePaymentItem;
use App\Models\Tenant\FeeType;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\Notice;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentCategory;
use App\Models\Tenant\StudentFee;
use App\Models\Tenant\StudentGuardian;
use App\Models\Tenant\Subject;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoSchoolSeeder — Populates a tenant database with realistic Pakistani
 * school demo data. Designed to be run inside tenancy context.
 *
 * Usage:
 *   - Via artisan: php artisan tenants:seed --class=App\Database\Seeders\DemoSchoolSeeder
 *   - Via kynex:reset-demo command
 */
class DemoSchoolSeeder extends Seeder
{
    private AcademicYear $academicYear;
    private Campus $campus;
    private array $classes = [];
    private array $sections = [];
    private array $subjects = [];
    private array $teachers = [];
    private array $students = [];
    private SchoolUser $adminUser;

    public function run(): void
    {
        $this->command?->info('🏫 Seeding Demo School Data...');

        $this->seedCmsSettings();
        $this->seedCmsContent();
        $this->seedCampusAndAcademicYear();
        $this->seedDepartmentsAndDesignations();
        $this->seedAdminUser();
        $this->seedTeachers();
        $this->seedCategories();
        $this->seedClassesAndSections();
        $this->seedSubjects();
        $this->seedClassSubjects();
        $this->seedStudents();
        $this->seedFeeStructure();
        $this->seedAttendance();
        $this->seedExamsAndResults();
        $this->seedHomework();
        $this->seedNotices();
        $this->seedLeaveTypes();
        $this->seedExpenseCategories();
        $this->seedClassRoutines();

        $this->command?->info('✅ Demo school data seeded successfully!');
        $this->command?->info("   Admin: admin@demo.kynexedu.com / password");
        $this->command?->info("   Teacher: teacher1@demo.kynexedu.com / password");
        $this->command?->info("   Students: STU-2025-0001 to STU-2025-0060");
    }

    // ── CMS Settings ───────────────────────────────────────────────

    private function seedCmsSettings(): void
    {
        CmsSetting::updateOrCreate(['id' => CmsSetting::first()?->id ?? Str::ulid()], [
            'school_name'          => 'Al-Huda Model School & College',
            'tagline'              => 'Excellence in Education Since 1998',
            'address'              => '45-B, Main Boulevard, Gulberg III, Lahore, Pakistan',
            'phone'                => '+92-42-35761234',
            'email'                => 'info@alhudamodel.edu.pk',
            'whatsapp'             => '+923001234567',
            'facebook_url'         => 'https://facebook.com/alhudamodel',
            'twitter_url'          => 'https://twitter.com/alhudamodel',
            'instagram_url'        => 'https://instagram.com/alhudamodel',
            'youtube_url'          => 'https://youtube.com/@alhudamodel',
            'primary_color'        => '#1e40af',
            'about_text'           => 'Al-Huda Model School & College has been a beacon of quality education in Lahore for over 25 years. We provide a holistic learning environment that nurtures academic excellence, character development, and Islamic values. Our campus features modern science labs, computer labs, a well-stocked library, and sports facilities including cricket and football grounds.',
            'principal_message'    => 'Education is not just about acquiring knowledge; it is about building character, developing critical thinking, and preparing our youth to be responsible citizens. At Al-Huda Model School, we believe every child has unique potential waiting to be discovered. Our dedicated faculty and comprehensive curriculum ensure that each student receives personalized attention and the best possible education.',
            'principal_name'       => 'Prof. Dr. Muhammad Akram Chaudhry',
            'admission_open'       => true,
            'admission_form_url'   => '/admissions',
        ]);

        $this->command?->info('  ✓ CMS Settings');
    }

    // ── CMS Content ────────────────────────────────────────────────

    private function seedCmsContent(): void
    {
        // Sliders
        CmsSlider::firstOrCreate(['title' => 'Welcome to Al-Huda Model School'], [
            'subtitle'    => 'Where every child discovers their potential',
            'button_text' => 'Apply Now',
            'button_url'  => '/admissions',
            'sort_order'  => 1,
            'is_active'   => true,
        ]);

        CmsSlider::firstOrCreate(['title' => 'State-of-the-Art Facilities'], [
            'subtitle'    => 'Modern labs, library & sports grounds',
            'button_text' => 'Take a Tour',
            'button_url'  => '/gallery',
            'sort_order'  => 2,
            'is_active'   => true,
        ]);

        CmsSlider::firstOrCreate(['title' => 'Admissions Open 2025-26'], [
            'subtitle'    => 'Limited seats available for Nursery to Grade 10',
            'button_text' => 'Enroll Today',
            'button_url'  => '/admissions',
            'sort_order'  => 3,
            'is_active'   => true,
        ]);

        // Announcements
        $announcements = [
            ['title' => 'Annual Sports Day 2025', 'content' => 'We are pleased to announce that the Annual Sports Day will be held on March 15, 2025. All students are encouraged to participate. Registration forms are available at the reception desk. Events include track & field, cricket, basketball, and table tennis. Parents are welcome to attend.', 'published_at' => now()->subDays(2)],
            ['title' => 'Parent-Teacher Meeting', 'content' => 'The quarterly Parent-Teacher Meeting for all classes will be held on February 28, 2025, from 9:00 AM to 1:00 PM. Parents are requested to attend and discuss their child\'s academic progress with class teachers.', 'published_at' => now()->subDays(5)],
            ['title' => 'Science Fair Registration Open', 'content' => 'Students of Grade 6 to Grade 10 can register for the Annual Science Fair 2025. The fair will be held in the school auditorium on April 5, 2025. Projects should be submitted by March 25. Cash prizes for top 3 positions!', 'published_at' => now()->subDay()],
            ['title' => 'Winter Vacation Notice', 'content' => 'The school will remain closed for winter vacation from December 20 to January 2. Classes will resume on January 3, 2025. Homework assignments have been sent via the parent portal.', 'published_at' => now()->subDays(60), 'expires_at' => now()->subDays(30)],
            ['title' => 'Fee Payment Deadline Reminder', 'content' => 'This is a reminder that the fee payment deadline for the current quarter is January 15. Late payment will incur a surcharge of PKR 500. Parents can pay fees online through JazzCash, EasyPaisa, or bank transfer.', 'published_at' => now()->subDays(10)],
        ];

        foreach ($announcements as $a) {
            CmsAnnouncement::firstOrCreate(['title' => $a['title']], array_merge($a, ['is_published' => true]));
        }

        // Gallery Albums
        $album = CmsGalleryAlbum::firstOrCreate(['title' => 'Campus Tour'], [
            'description'  => 'A tour of our beautiful campus and facilities',
            'sort_order'   => 1,
            'is_published' => true,
        ]);

        CmsGalleryAlbum::firstOrCreate(['title' => 'Sports Day 2024'], [
            'description'  => 'Highlights from the Annual Sports Day',
            'sort_order'   => 2,
            'is_published' => true,
        ]);

        CmsGalleryAlbum::firstOrCreate(['title' => 'Science Fair 2024'], [
            'description'  => 'Student projects and experiments at the Science Fair',
            'sort_order'   => 3,
            'is_published' => true,
        ]);

        // CMS Pages
        CmsPage::firstOrCreate(['slug' => 'terms-and-conditions'], [
            'title'      => 'Terms & Conditions',
            'content'    => '<h2>Terms of Use</h2><p>These terms govern the use of the Al-Huda Model School online portal. By accessing this portal, you agree to these terms.</p><h3>1. Account Usage</h3><p>Your login credentials are personal and should not be shared with others.</p><h3>2. Fee Payments</h3><p>Online fee payments are processed through secure payment gateways. All transactions are final.</p><h3>3. Privacy</h3><p>We respect your privacy and protect your personal data in accordance with applicable laws.</p>',
            'is_published' => true,
            'published_at' => now(),
            'sort_order'   => 1,
        ]);

        CmsPage::firstOrCreate(['slug' => 'privacy-policy'], [
            'title'      => 'Privacy Policy',
            'content'    => '<h2>Privacy Policy</h2><p>Al-Huda Model School is committed to protecting the privacy of students, parents, and staff.</p><h3>Data Collection</h3><p>We collect personal information necessary for admission, academic records, and communication purposes.</p><h3>Data Security</h3><p>All data is stored securely and access is restricted to authorized personnel only.</p><h3>Third Party Sharing</h3><p>We do not sell or share personal data with third parties except as required by law.</p>',
            'is_published' => true,
            'published_at' => now(),
            'sort_order'   => 2,
        ]);

        $this->command?->info('  ✓ CMS Content (sliders, announcements, gallery, pages)');
    }

    // ── Campus & Academic Year ─────────────────────────────────────

    private function seedCampusAndAcademicYear(): void
    {
        $this->campus = Campus::firstOrCreate(['name' => 'Main Campus'], [
            'address'    => '45-B, Main Boulevard, Gulberg III, Lahore',
            'phone'      => '+92-42-35761234',
            'is_active'  => true,
        ]);

        $this->academicYear = AcademicYear::firstOrCreate(['name' => '2024-2025'], [
            'start_date' => Carbon::parse('2024-04-01'),
            'end_date'   => Carbon::parse('2025-03-31'),
            'is_current' => true,
        ]);

        $this->command?->info('  ✓ Campus & Academic Year');
    }

    // ── Departments & Designations ─────────────────────────────────

    private function seedDepartmentsAndDesignations(): void
    {
        $departments = ['Administration', 'Science', 'Arts & Humanities', 'Mathematics', 'Languages', 'Physical Education', 'Computer Science'];
        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept]);
        }

        $designations = ['Principal', 'Vice Principal', 'Head of Department', 'Senior Teacher', 'Teacher', 'Lab Assistant', 'Office Clerk', 'Librarian', 'Accountant', 'Receptionist'];
        foreach ($designations as $desig) {
            Designation::firstOrCreate(['name' => $desig]);
        }

        $this->command?->info('  ✓ Departments & Designations');
    }

    // ── Admin User ─────────────────────────────────────────────────

    private function seedAdminUser(): void
    {
        $this->adminUser = SchoolUser::firstOrCreate(['email' => 'admin@demo.kynexedu.com'], [
            'name'      => 'School Administrator',
            'password'  => Hash::make('password'),
            'phone'     => '+923001234567',
            'campus_id' => $this->campus->id,
            'is_active' => true,
        ]);

        if (! $this->adminUser->hasRole('SCHOOL_ADMIN')) {
            $this->adminUser->assignRole('SCHOOL_ADMIN');
        }

        $this->command?->info('  ✓ Admin User');
    }

    // ── Teachers ───────────────────────────────────────────────────

    private function seedTeachers(): void
    {
        $scienceDept = Department::where('name', 'Science')->first();
        $mathDept = Department::where('name', 'Mathematics')->first();
        $langDept = Department::where('name', 'Languages')->first();
        $csDept = Department::where('name', 'Computer Science')->first();
        $artsDept = Department::where('name', 'Arts & Humanities')->first();
        $peDept = Department::where('name', 'Physical Education')->first();

        $seniorTeacher = Designation::where('name', 'Senior Teacher')->first();
        $teacher = Designation::where('name', 'Teacher')->first();

        $teacherData = [
            ['name' => 'Mr. Ahmed Raza',   'email' => 'teacher1@demo.kynexedu.com', 'dept' => $scienceDept,  'desig' => $seniorTeacher, 'gender' => 'male',   'specialization' => 'Physics'],
            ['name' => 'Ms. Fatima Noor',   'email' => 'teacher2@demo.kynexedu.com', 'dept' => $mathDept,     'desig' => $seniorTeacher, 'gender' => 'female', 'specialization' => 'Mathematics'],
            ['name' => 'Mr. Usman Ali',     'email' => 'teacher3@demo.kynexedu.com', 'dept' => $langDept,     'desig' => $teacher,       'gender' => 'male',   'specialization' => 'English Literature'],
            ['name' => 'Ms. Ayesha Khan',   'email' => 'teacher4@demo.kynexedu.com', 'dept' => $langDept,     'desig' => $teacher,       'gender' => 'female', 'specialization' => 'Urdu'],
            ['name' => 'Mr. Bilal Hussain', 'email' => 'teacher5@demo.kynexedu.com', 'dept' => $csDept,       'desig' => $teacher,       'gender' => 'male',   'specialization' => 'Computer Science'],
            ['name' => 'Ms. Sana Malik',    'email' => 'teacher6@demo.kynexedu.com', 'dept' => $scienceDept,  'desig' => $teacher,       'gender' => 'female', 'specialization' => 'Chemistry'],
            ['name' => 'Mr. Tariq Jamil',   'email' => 'teacher7@demo.kynexedu.com', 'dept' => $artsDept,     'desig' => $teacher,       'gender' => 'male',   'specialization' => 'Islamiat'],
            ['name' => 'Ms. Rabia Zafar',   'email' => 'teacher8@demo.kynexedu.com', 'dept' => $scienceDept,  'desig' => $teacher,       'gender' => 'female', 'specialization' => 'Biology'],
            ['name' => 'Mr. Hassan Sheikh',  'email' => 'teacher9@demo.kynexedu.com', 'dept' => $peDept,       'desig' => $teacher,       'gender' => 'male',   'specialization' => 'Physical Education'],
            ['name' => 'Ms. Nida Perveen',  'email' => 'teacher10@demo.kynexedu.com', 'dept' => $artsDept,    'desig' => $teacher,       'gender' => 'female', 'specialization' => 'Pakistan Studies'],
        ];

        foreach ($teacherData as $t) {
            $user = SchoolUser::firstOrCreate(['email' => $t['email']], [
                'name'      => $t['name'],
                'password'  => Hash::make('password'),
                'phone'     => '+923' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'campus_id' => $this->campus->id,
                'is_active' => true,
            ]);

            if (! $user->hasRole('TEACHER')) {
                $user->assignRole('TEACHER');
            }

            StaffProfile::firstOrCreate(['school_user_id' => $user->id], [
                'department_id'  => $t['dept']?->id,
                'designation_id' => $t['desig']?->id,
                'employee_id'    => 'EMP-' . str_pad((string) (count($this->teachers) + 1), 4, '0', STR_PAD_LEFT),
                'joining_date'   => Carbon::parse('2020-01-01')->addDays(random_int(0, 1460)),
                'qualification'  => 'M.Ed / M.Sc ' . $t['specialization'],
                'experience_years' => random_int(3, 20),
            ]);

            $this->teachers[] = $user;
        }

        $this->command?->info('  ✓ Teachers (' . count($this->teachers) . ')');
    }

    // ── Student Categories ─────────────────────────────────────────

    private function seedCategories(): void
    {
        $categories = ['General', 'Hafiz Discount', 'Staff Child', 'Sibling Discount', 'Scholarship'];
        foreach ($categories as $cat) {
            StudentCategory::firstOrCreate(['name' => $cat]);
        }

        $this->command?->info('  ✓ Student Categories');
    }

    // ── Classes & Sections ─────────────────────────────────────────

    private function seedClassesAndSections(): void
    {
        $classNames = [
            ['name' => 'Nursery',  'numeric' => 0,  'sort' => 1],
            ['name' => 'Prep',     'numeric' => 0,  'sort' => 2],
            ['name' => 'Class 1',  'numeric' => 1,  'sort' => 3],
            ['name' => 'Class 2',  'numeric' => 2,  'sort' => 4],
            ['name' => 'Class 3',  'numeric' => 3,  'sort' => 5],
            ['name' => 'Class 4',  'numeric' => 4,  'sort' => 6],
            ['name' => 'Class 5',  'numeric' => 5,  'sort' => 7],
            ['name' => 'Class 6',  'numeric' => 6,  'sort' => 8],
            ['name' => 'Class 7',  'numeric' => 7,  'sort' => 9],
            ['name' => 'Class 8',  'numeric' => 8,  'sort' => 10],
            ['name' => 'Class 9',  'numeric' => 9,  'sort' => 11],
            ['name' => 'Class 10', 'numeric' => 10, 'sort' => 12],
        ];

        $sectionNames = ['A', 'B'];

        foreach ($classNames as $c) {
            $class = SchoolClass::firstOrCreate(['name' => $c['name']], [
                'numeric_level' => $c['numeric'],
                'sort_order'    => $c['sort'],
            ]);
            $this->classes[$c['name']] = $class;

            foreach ($sectionNames as $s) {
                $section = Section::firstOrCreate([
                    'class_id' => $class->id,
                    'name'     => $s,
                ], [
                    'capacity' => 40,
                ]);
                $this->sections[$c['name'] . '-' . $s] = $section;
            }
        }

        $this->command?->info('  ✓ Classes (' . count($this->classes) . ') & Sections (' . count($this->sections) . ')');
    }

    // ── Subjects ───────────────────────────────────────────────────

    private function seedSubjects(): void
    {
        $subjectList = [
            ['name' => 'English',           'code' => 'ENG',  'type' => 'compulsory'],
            ['name' => 'Urdu',              'code' => 'URD',  'type' => 'compulsory'],
            ['name' => 'Mathematics',       'code' => 'MATH', 'type' => 'compulsory'],
            ['name' => 'Science',           'code' => 'SCI',  'type' => 'compulsory'],
            ['name' => 'Islamiat',          'code' => 'ISL',  'type' => 'compulsory'],
            ['name' => 'Pakistan Studies',  'code' => 'PST',  'type' => 'compulsory'],
            ['name' => 'Computer Science',  'code' => 'CS',   'type' => 'elective'],
            ['name' => 'Physics',           'code' => 'PHY',  'type' => 'elective'],
            ['name' => 'Chemistry',         'code' => 'CHM',  'type' => 'elective'],
            ['name' => 'Biology',           'code' => 'BIO',  'type' => 'elective'],
            ['name' => 'General Knowledge', 'code' => 'GK',   'type' => 'compulsory'],
            ['name' => 'Drawing',           'code' => 'DRW',  'type' => 'optional'],
        ];

        foreach ($subjectList as $s) {
            $subject = Subject::firstOrCreate(['code' => $s['code']], [
                'name' => $s['name'],
                'subject_type' => $s['type'],
            ]);
            $this->subjects[$s['code']] = $subject;
        }

        $this->command?->info('  ✓ Subjects (' . count($this->subjects) . ')');
    }

    // ── Class-Subject Mapping ──────────────────────────────────────

    private function seedClassSubjects(): void
    {
        $count = 0;

        // Primary classes (1-5): ENG, URD, MATH, SCI, ISL, GK, DRW
        $primarySubjects = ['ENG', 'URD', 'MATH', 'SCI', 'ISL', 'GK', 'DRW'];
        $middleSubjects = ['ENG', 'URD', 'MATH', 'SCI', 'ISL', 'PST', 'CS'];
        $highSubjects = ['ENG', 'URD', 'MATH', 'PHY', 'CHM', 'BIO', 'ISL', 'PST', 'CS'];

        foreach ($this->classes as $name => $class) {
            $numeric = $class->numeric_level ?? 0;
            $subjectCodes = match (true) {
                $numeric <= 5 => $primarySubjects,
                $numeric <= 8 => $middleSubjects,
                default       => $highSubjects,
            };

            $teacherIndex = 0;
            foreach ($subjectCodes as $code) {
                $subject = $this->subjects[$code] ?? null;
                if (! $subject) continue;

                $teacher = $this->teachers[$teacherIndex % count($this->teachers)] ?? null;

                ClassSubject::firstOrCreate([
                    'class_id'   => $class->id,
                    'subject_id' => $subject->id,
                ], [
                    'teacher_id' => $teacher?->id,
                ]);

                $count++;
                $teacherIndex++;
            }
        }

        $this->command?->info('  ✓ Class-Subject Mappings (' . $count . ')');
    }

    // ── Students ───────────────────────────────────────────────────

    private function seedStudents(): void
    {
        $generalCat = StudentCategory::where('name', 'General')->first();
        $hafizCat = StudentCategory::where('name', 'Hafiz Discount')->first();

        $firstNamesMale = ['Ahmed', 'Muhammad', 'Ali', 'Hassan', 'Usman', 'Abdullah', 'Bilal', 'Hamza', 'Ibrahim', 'Zain', 'Omar', 'Saad', 'Talha', 'Faisal', 'Kamran'];
        $firstNamesFemale = ['Fatima', 'Ayesha', 'Maryam', 'Zainab', 'Khadija', 'Hira', 'Sana', 'Noor', 'Amina', 'Rabia', 'Sara', 'Laiba', 'Iqra', 'Aliza', 'Mahnoor'];
        $lastNames = ['Khan', 'Ahmed', 'Ali', 'Raza', 'Malik', 'Sheikh', 'Butt', 'Chaudhry', 'Siddiqui', 'Hashmi', 'Qureshi', 'Iqbal', 'Mirza', 'Tariq', 'Javed'];

        $studentNumber = 1;
        $targetClasses = ['Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'];

        foreach ($targetClasses as $className) {
            $class = $this->classes[$className] ?? null;
            if (! $class) continue;

            foreach (['A', 'B'] as $sectionName) {
                $section = $this->sections[$className . '-' . $sectionName] ?? null;
                if (! $section) continue;

                // 5 students per section = 60 total
                for ($i = 0; $i < 5; $i++) {
                    $gender = $i % 2 === 0 ? 'male' : 'female';
                    $firstNames = $gender === 'male' ? $firstNamesMale : $firstNamesFemale;
                    $firstName = $firstNames[array_rand($firstNames)];
                    $lastName = $lastNames[array_rand($lastNames)];
                    $admissionNo = 'STU-2025-' . str_pad((string) $studentNumber, 4, '0', STR_PAD_LEFT);

                    $user = SchoolUser::firstOrCreate(['email' => "student{$studentNumber}@demo.kynexedu.com"], [
                        'name'      => "{$firstName} {$lastName}",
                        'password'  => Hash::make('password'),
                        'phone'     => '+923' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                        'campus_id' => $this->campus->id,
                        'is_active' => true,
                    ]);

                    if (! $user->hasRole('STUDENT')) {
                        $user->assignRole('STUDENT');
                    }

                    $student = Student::firstOrCreate(['admission_number' => $admissionNo], [
                        'school_user_id'   => $user->id,
                        'academic_year_id' => $this->academicYear->id,
                        'class_id'         => $class->id,
                        'section_id'       => $section->id,
                        'campus_id'        => $this->campus->id,
                        'category_id'      => $studentNumber % 7 === 0 ? $hafizCat?->id : $generalCat?->id,
                        'first_name'       => $firstName,
                        'last_name'        => $lastName,
                        'gender'           => $gender === 'male' ? StudentGender::Male : StudentGender::Female,
                        'date_of_birth'    => Carbon::now()->subYears(10 + $class->numeric_level)->subDays(random_int(0, 365)),
                        'roll_number'      => $i + 1,
                        'admission_date'   => Carbon::parse('2024-04-01'),
                        'status'           => StudentStatus::Enrolled,
                        'blood_group'      => collect(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])->random(),
                    ]);

                    // Guardian
                    $guardianFirstName = $firstNamesMale[array_rand($firstNamesMale)];
                    StudentGuardian::firstOrCreate([
                        'student_id' => $student->id,
                        'name'       => "Mr. {$guardianFirstName} {$lastName}",
                    ], [
                        'relationship'       => 'father',
                        'phone'          => '+923' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                        'email'          => strtolower("{$guardianFirstName}.{$lastName}") . '@gmail.com',
                        'occupation'     => collect(['Businessman', 'Engineer', 'Doctor', 'Teacher', 'Government Officer', 'Lawyer'])->random(),
                        'is_primary_contact'     => true,
                    ]);

                    $this->students[] = $student;
                    $studentNumber++;
                }
            }
        }

        $this->command?->info('  ✓ Students (' . count($this->students) . ') with Guardians');
    }

    // ── Fee Structure ──────────────────────────────────────────────

    private function seedFeeStructure(): void
    {
        $feeGroup = FeeGroup::firstOrCreate(['name' => 'Monthly Fee 2024-25'], [
            'description' => 'Regular monthly tuition fee structure',
        ]);

        $tuitionType = FeeType::firstOrCreate(['name' => 'Tuition Fee']);

        $labType = FeeType::firstOrCreate(['name' => 'Lab Fee']);

        $compType = FeeType::firstOrCreate(['name' => 'Computer Lab Fee']);

        // Fee Masters — per class
        foreach ($this->classes as $name => $class) {
            $numeric = $class->numeric_level ?? 0;

            // Tuition: PKR 2000-4000 based on class level
            $baseFee = 200_000 + ($numeric * 20_000); // paisas

            FeeMaster::firstOrCreate([
                'fee_type_id'      => $tuitionType->id,
                'class_id'         => $class->id,
                'academic_year_id' => $this->academicYear->id,
            ], [
                'amount_paisas' => $baseFee,
            ]);

            // Lab fee for Class 6+
            if ($numeric >= 6) {
                FeeMaster::firstOrCreate([
                    'fee_group_id'     => $feeGroup->id,
                    'fee_type_id'      => $labType->id,
                    'class_id'         => $class->id,
                    'academic_year_id' => $this->academicYear->id,
                ], [
                    'amount_paisas' => 50_000, // PKR 500
                                    ]);
            }

            // Computer fee for Class 5+
            if ($numeric >= 5) {
                FeeMaster::firstOrCreate([
                    'fee_group_id'     => $feeGroup->id,
                    'fee_type_id'      => $compType->id,
                    'class_id'         => $class->id,
                    'academic_year_id' => $this->academicYear->id,
                ], [
                    'amount_paisas' => 30_000, // PKR 300
                                    ]);
            }
        }

        // Assign fees to students + create some payments
        $paymentMethods = [FeePaymentMethod::Cash, FeePaymentMethod::BankTransfer, FeePaymentMethod::JazzCash, FeePaymentMethod::EasyPaisa];

        foreach ($this->students as $index => $student) {
            $feeMasters = FeeMaster::where('class_id', $student->class_id)
                ->where('academic_year_id', $this->academicYear->id)
                ->get();

            foreach ($feeMasters as $fm) {
                $fee = StudentFee::firstOrCreate([
                    'student_id'       => $student->id,
                    'fee_type_id'      => $fm->fee_type_id,
                    'academic_year_id' => $this->academicYear->id,
                ], [
                    'amount_paisas'    => $fm->amount_paisas,
                    'discount_paisas'  => 0,
                    'paid_paisas'      => 0,
                    'due_date'         => Carbon::parse('2025-01-15'),
                    'status'           => StudentFeeStatus::Unpaid,
                ]);

                // 70% of students have paid
                if ($index % 10 < 7) {
                    $fee->update([
                        'paid_paisas' => $fm->amount_paisas,
                        'status'      => StudentFeeStatus::Paid,
                    ]);

                    $payment = FeePayment::firstOrCreate([
                        'student_id'     => $student->id,
                        'receipt_number' => 'RCP-' . Str::upper(Str::random(8)),
                    ], [
                        'total_amount_paisas' => $fm->amount_paisas,
                        'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                        'collected_by'   => $this->adminUser->id,
                        'payment_date'   => now()->subDays(random_int(1, 30)),
                    ]);

                    FeePaymentItem::firstOrCreate([
                        'payment_id' => $payment->id,
                        'student_fee_id' => $fee->id,
                    ], [
                        'amount_paisas' => $fm->amount_paisas,
                    ]);
                }
            }
        }

        $this->command?->info('  ✓ Fee Structure & Payments');
    }

    // ── Attendance ─────────────────────────────────────────────────

    private function seedAttendance(): void
    {
        $count = 0;
        $statuses = [
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Absent,
            AttendanceStatus::Late,
            AttendanceStatus::Excused,
        ];

        // Last 5 working days of attendance
        $dates = collect();
        $date = Carbon::now();
        while ($dates->count() < 5) {
            if ($date->isWeekday()) {
                $dates->push($date->copy());
            }
            $date->subDay();
        }

        foreach ($this->students as $student) {
            foreach ($dates as $d) {
                $status = $statuses[array_rand($statuses)];

                AttendanceRecord::firstOrCreate([
                    'student_id' => $student->id,
                    'date'       => $d->toDateString(),
                ], [
                    'class_id'         => $student->class_id,
                    'section_id'       => $student->section_id,
                    'academic_year_id' => $this->academicYear->id,
                    'status'           => $status,
                    'marked_by'        => $this->teachers[0]->id ?? $this->adminUser->id,
                    'late_minutes'     => $status === AttendanceStatus::Late ? random_int(5, 30) : null,
                ]);

                $count++;
            }
        }

        $this->command?->info('  ✓ Attendance Records (' . $count . ')');
    }

    // ── Exams & Results ────────────────────────────────────────────

    private function seedExamsAndResults(): void
    {
        $midTerm = Exam::firstOrCreate(['name' => 'Mid-Term Examination 2024'], [
            'academic_year_id' => $this->academicYear->id,
            'start_date'       => Carbon::parse('2024-10-01'),
            'end_date'         => Carbon::parse('2024-10-15'),
            'status'           => ExamStatus::Published,
            'publish_results'  => true,
            'created_by'       => $this->adminUser->id,
        ]);

        $final = Exam::firstOrCreate(['name' => 'Final Examination 2025'], [
            'academic_year_id' => $this->academicYear->id,
            'start_date'       => Carbon::parse('2025-02-15'),
            'end_date'         => Carbon::parse('2025-02-28'),
            'status'           => ExamStatus::Active,
            'publish_results'  => false,
            'created_by'       => $this->adminUser->id,
        ]);

        // Generate results for mid-term only
        $resultCount = 0;
        foreach ($this->students as $student) {
            $totalMarks = 0;
            $obtainedMarks = 0;
            $subjectCount = 0;
            $passed = 0;
            $failed = 0;

            $classSubjects = ClassSubject::where('class_id', $student->class_id)->get();

            foreach ($classSubjects as $cs) {
                $fullMarks = 100;
                $obtained = random_int(35, 98);
                $totalMarks += $fullMarks;
                $obtainedMarks += $obtained;
                $subjectCount++;

                if ($obtained >= 40) {
                    $passed++;
                } else {
                    $failed++;
                }
            }

            if ($subjectCount === 0) continue;

            $percentage = round(($obtainedMarks / $totalMarks) * 100, 2);
            $grade = match (true) {
                $percentage >= 90 => 'A+',
                $percentage >= 80 => 'A',
                $percentage >= 70 => 'B',
                $percentage >= 60 => 'C',
                $percentage >= 50 => 'D',
                $percentage >= 40 => 'E',
                default           => 'F',
            };

            $gpa = match (true) {
                $percentage >= 90 => 4.0,
                $percentage >= 80 => 3.7,
                $percentage >= 70 => 3.3,
                $percentage >= 60 => 3.0,
                $percentage >= 50 => 2.5,
                $percentage >= 40 => 2.0,
                default           => 0.0,
            };

            ExamResult::firstOrCreate([
                'exam_id'    => $midTerm->id,
                'student_id' => $student->id,
            ], [
                'class_id'        => $student->class_id,
                'total_marks'     => $totalMarks,
                'marks_obtained'  => $obtainedMarks,
                'percentage'      => $percentage,
                'grade'           => $grade,
                'grade_point'     => $gpa,
                'rank'            => null,
                'status'          => $failed > 0 ? ExamResultStatus::Failed : ExamResultStatus::Passed,
            ]);

            $resultCount++;
        }

        $this->command?->info('  ✓ Exams & Results (' . $resultCount . ')');
    }

    // ── Homework ───────────────────────────────────────────────────

    private function seedHomework(): void
    {
        $homeworkItems = [
            ['title' => 'English Essay — My Country Pakistan', 'subject' => 'ENG', 'days_from_now' => 3],
            ['title' => 'Mathematics — Chapter 5 Exercise',    'subject' => 'MATH', 'days_from_now' => 2],
            ['title' => 'Science — Photosynthesis Diagram',    'subject' => 'SCI', 'days_from_now' => 4],
            ['title' => 'Urdu — Ghazal Summary Writing',       'subject' => 'URD', 'days_from_now' => 5],
            ['title' => 'Islamiat — Surah Translation',        'subject' => 'ISL', 'days_from_now' => -1],
            ['title' => 'Computer — HTML Practice Page',       'subject' => 'CS', 'days_from_now' => 7],
        ];

        $count = 0;
        foreach (['Class 8', 'Class 9', 'Class 10'] as $className) {
            $class = $this->classes[$className] ?? null;
            if (! $class) continue;

            foreach (['A', 'B'] as $sec) {
                $section = $this->sections[$className . '-' . $sec] ?? null;
                if (! $section) continue;

                foreach ($homeworkItems as $hw) {
                    $subject = $this->subjects[$hw['subject']] ?? null;
                    if (! $subject) continue;

                    // Skip CS for classes that don't have it
                    $hasSubject = ClassSubject::where('class_id', $class->id)
                        ->where('subject_id', $subject->id)->exists();
                    if (! $hasSubject) continue;

                    $teacher = $this->teachers[array_rand($this->teachers)];

                    HomeworkAssignment::firstOrCreate([
                        'class_id'   => $class->id,
                        'section_id' => $section->id,
                        'subject_id' => $subject->id,
                        'title'      => $hw['title'],
                    ], [
                        'teacher_id'  => $teacher->id,
                        'description' => 'Complete the assigned work neatly in your notebooks. Show all working where applicable. Due date must be followed strictly.',
                        'due_date'    => now()->addDays($hw['days_from_now']),
                    ]);

                    $count++;
                }
            }
        }

        $this->command?->info('  ✓ Homework Assignments (' . $count . ')');
    }

    // ── Notices ────────────────────────────────────────────────────

    private function seedNotices(): void
    {
        $notices = [
            [
                'title'        => 'Important: Uniform Policy Update',
                'content'      => 'Starting from February 1, 2025, all students must wear the updated winter uniform. The new uniform includes a navy blue sweater with the school monogram. Students not in proper uniform will be sent home.',
                'target_roles' => ['STUDENT', 'PARENT'],
            ],
            [
                'title'        => 'Staff Meeting — Monday 9:00 AM',
                'content'      => 'All teaching and non-teaching staff are required to attend the monthly staff meeting on Monday at 9:00 AM in the conference hall. Agenda includes curriculum review and upcoming events planning.',
                'target_roles' => ['TEACHER', 'SCHOOL_ADMIN'],
            ],
            [
                'title'        => 'Library Books Return Deadline',
                'content'      => 'All borrowed library books must be returned by January 31, 2025. Students with overdue books will be charged a late fee of PKR 10 per day per book. New books for the spring semester will be available from February 5.',
                'target_roles' => ['STUDENT', 'TEACHER', 'PARENT'],
            ],
            [
                'title'        => 'Transport Route Change Notice',
                'content'      => 'Due to road construction on Canal Road, Route 3 (Johar Town to School) will be diverted via Ferozepur Road starting January 20. The bus will depart 10 minutes earlier. Please adjust your schedule accordingly.',
                'target_roles' => ['STUDENT', 'PARENT'],
            ],
            [
                'title'        => 'Mid-Term Results Available',
                'content'      => 'Mid-Term examination results for all classes have been published. Parents can view the results through the parent portal or mobile app. Report cards will be distributed during the PTM.',
                'target_roles' => ['STUDENT', 'PARENT', 'TEACHER'],
            ],
        ];

        foreach ($notices as $n) {
            Notice::firstOrCreate(['title' => $n['title']], [
                'content'      => $n['content'],
                'target_roles' => $n['target_roles'],
                'is_published' => true,
                'published_at' => now()->subDays(random_int(1, 15)),
                'created_by'   => $this->adminUser->id,
            ]);
        }

        $this->command?->info('  ✓ Notices (' . count($notices) . ')');
    }

    // ── Leave Types ────────────────────────────────────────────────

    private function seedLeaveTypes(): void
    {
        $leaveTypes = [
            ['name' => 'Casual Leave',     'max_days_per_year' => 12, 'applicable_to' => 'staff'],
            ['name' => 'Sick Leave',        'max_days_per_year' => 10, 'applicable_to' => 'staff'],
            ['name' => 'Earned Leave',      'max_days_per_year' => 15, 'applicable_to' => 'staff'],
            ['name' => 'Maternity Leave',   'max_days_per_year' => 90, 'applicable_to' => 'staff'],
            ['name' => 'Student Leave',     'max_days_per_year' => 30, 'applicable_to' => 'student'],
            ['name' => 'Medical Leave',     'max_days_per_year' => 15, 'applicable_to' => 'both'],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::firstOrCreate(['name' => $lt['name']], $lt);
        }

        $this->command?->info('  ✓ Leave Types');
    }

    // ── Expense Categories ─────────────────────────────────────────

    private function seedExpenseCategories(): void
    {
        $categories = [
            'Salaries & Wages',
            'Utilities (Electricity, Gas, Water)',
            'Stationery & Supplies',
            'Maintenance & Repairs',
            'Transport & Fuel',
            'Events & Functions',
            'Lab Equipment',
            'Furniture & Fixtures',
            'IT & Software',
            'Miscellaneous',
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat]);
        }

        $this->command?->info('  ✓ Expense Categories');
    }

    // ── Class Routines (Timetable) ─────────────────────────────────

    private function seedClassRoutines(): void
    {
        $count = 0;
        $periods = [
            ['start' => '08:00', 'end' => '08:40'],
            ['start' => '08:40', 'end' => '09:20'],
            ['start' => '09:20', 'end' => '10:00'],
            ['start' => '10:00', 'end' => '10:15'], // Break
            ['start' => '10:15', 'end' => '10:55'],
            ['start' => '10:55', 'end' => '11:35'],
            ['start' => '11:35', 'end' => '12:15'],
            ['start' => '12:15', 'end' => '12:45'], // Lunch/Prayer
            ['start' => '12:45', 'end' => '13:25'],
        ];

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach (['Class 8', 'Class 9', 'Class 10'] as $className) {
            $class = $this->classes[$className] ?? null;
            if (! $class) continue;

            $classSubjects = ClassSubject::where('class_id', $class->id)->with('subject')->get();
            if ($classSubjects->isEmpty()) continue;

            foreach (['A'] as $sec) {
                $section = $this->sections[$className . '-' . $sec] ?? null;
                if (! $section) continue;

                foreach ($days as $day) {
                    $subjectIndex = 0;
                    foreach ($periods as $periodIndex => $period) {
                        // Skip breaks
                        if ($periodIndex === 3 || $periodIndex === 7) continue;

                        $cs = $classSubjects[$subjectIndex % $classSubjects->count()];

                        ClassRoutine::firstOrCreate([
                            'class_id'   => $class->id,
                            'section_id' => $section->id,
                            'day_of_week' => $day,
                            'period_number' => $periodIndex + 1,
                        ], [
                            'start_time' => $period['start'],
                            'end_time'   => $period['end'],
                            'subject_id' => $cs->subject_id,
                            'teacher_id' => $cs->teacher_id ?? $this->teachers[0]?->id,
                            'room_number' => $className . '-' . $sec,
                        ]);

                        $count++;
                        $subjectIndex++;
                    }
                }
            }
        }

        $this->command?->info('  ✓ Class Routines (' . $count . ')');
    }
}
