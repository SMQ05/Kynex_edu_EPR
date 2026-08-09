<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * United States demo profile — Lincoln Heights Academy, Austin TX.
 *
 * A mid-size independent K-12 day school: the shape of institution that
 * actually buys a system like this in the US market, so the numbers,
 * addresses and payment mix all read as plausible to an American buyer.
 *
 * Two deliberate choices worth knowing:
 *
 *  - PHONE NUMBERS use the NANP fictional convention: a real area code with
 *    exchange 555 and line number in the 0100-0199 block, which is formally
 *    reserved for fictional use. Nobody's actual phone is in the demo data.
 *
 *  - guardianDocumentNumber() returns NULL on purpose. The Pakistani profile
 *    supplies a CNIC because Pakistani schools genuinely record it; the US
 *    equivalent would be a Social Security number, which US schools do not
 *    collect for this and which should never be synthesised into a database
 *    column. Guardians are identified by name, email and phone instead, and
 *    students by their district student ID (admission_number).
 */
final class UsaProfile extends DemoProfile
{
    public function maleFirstNames(): array
    {
        return [
            'James', 'Michael', 'Ethan', 'Noah', 'Liam', 'Mason', 'Lucas',
            'Jackson', 'Aiden', 'Caleb', 'Owen', 'Wyatt', 'Nathan', 'Isaac',
            'Julian', 'Levi', 'Miles', 'Cooper', 'Elijah', 'Carter',
            'Jordan', 'Tyler', 'Brandon', 'Marcus', 'Andre', 'Devin',
            'Hector', 'Diego', 'Mateo', 'Andres', 'Omar', 'Malik',
            'Connor', 'Grayson', 'Dominic', 'Silas', 'Bennett', 'Theo',
            'Roman', 'Xavier',
        ];
    }

    public function femaleFirstNames(): array
    {
        return [
            'Emma', 'Olivia', 'Ava', 'Sophia', 'Isabella', 'Mia', 'Charlotte',
            'Amelia', 'Harper', 'Evelyn', 'Abigail', 'Emily', 'Ella', 'Grace',
            'Chloe', 'Lily', 'Nora', 'Hazel', 'Zoey', 'Riley',
            'Maya', 'Aaliyah', 'Jasmine', 'Destiny', 'Imani', 'Nia',
            'Camila', 'Valentina', 'Lucia', 'Elena', 'Fatima', 'Layla',
            'Quinn', 'Sadie', 'Josephine', 'Clara', 'Ruby', 'Ivy',
            'Delaney', 'Rosalie',
        ];
    }

    public function surnames(): array
    {
        return [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia',
            'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez',
            'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore',
            'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White',
            'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
            'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott',
            'Torres', 'Nguyen', 'Hill', 'Flores', 'Rivera', 'Campbell',
            'Mitchell', 'Carter', 'Roberts', 'Patel', 'Okafor', 'Chen',
        ];
    }

    public function cities(): array
    {
        return [
            ['name' => 'Austin',      'province' => 'TX', 'postal' => '78701'],
            ['name' => 'Round Rock',  'province' => 'TX', 'postal' => '78664'],
            ['name' => 'Cedar Park',  'province' => 'TX', 'postal' => '78613'],
            ['name' => 'Pflugerville', 'province' => 'TX', 'postal' => '78660'],
            ['name' => 'Georgetown',  'province' => 'TX', 'postal' => '78626'],
        ];
    }

    /**
     * US blood-type distribution (approximate population shares).
     * O+ dominates, unlike the B+-heavy South Asian distribution.
     */
    public function bloodGroupWeights(): array
    {
        return [
            'O+' => 37, 'A+' => 36, 'B+' => 9, 'AB+' => 3,
            'O-' => 6,  'A-' => 6,  'B-' => 2, 'AB-' => 1,
        ];
    }

    public function parentOccupations(): array
    {
        return [
            'Software Engineer', 'Registered Nurse', 'Teacher', 'Accountant',
            'Attorney', 'Physician', 'Project Manager', 'Sales Director',
            'Electrician', 'Firefighter', 'Police Officer', 'Small Business Owner',
            'Data Analyst', 'Marketing Manager', 'Civil Engineer', 'Pharmacist',
            'Dental Hygienist', 'Real Estate Agent', 'Logistics Coordinator',
            'HR Business Partner',
        ];
    }

    /**
     * US school payment mix: card and ACH dominate, paper checks persist,
     * cash is rare. Keys match what FeesSeeder writes to fee_payments.
     */
    public function paymentMethods(): array
    {
        return [
            'card' => 40,
            'bank_transfer' => 30,
            'cheque' => 18,
            'online' => 8,
            'cash' => 4,
        ];
    }

    /**
     * NANP fictional number: (AAA) 555-01XX. The 555-0100..0199 line range
     * is reserved for fictional use, so these can never reach a real person.
     *
     * MUST stay <= 15 characters: staff_profiles.personal_whatsapp is
     * varchar(15), the tightest phone column in the schema (every other one
     * is varchar(20)). "(512) 555-0127" is 14. The Pakistani profile fits
     * because +923424834763 is only 13, so this constraint never surfaced
     * before — dropping the "+1 " prefix keeps the familiar US shape and
     * leaves a character of headroom.
     */
    public function phone(): string
    {
        $areaCodes = ['512', '737', '469', '214', '713'];

        return sprintf(
            '(%s) 555-%04d',
            $areaCodes[mt_rand(0, count($areaCodes) - 1)],
            mt_rand(100, 199),
        );
    }

    public function address(?string $cityName = null): array
    {
        $cities = $this->cities();
        $city = $cityName;
        $postal = null;

        foreach ($cities as $row) {
            if ($row['name'] === $city) {
                $postal = $row['postal'];
                break;
            }
        }

        if ($city === null || $postal === null) {
            $chosen = $this->pick($cities);
            $city = $chosen['name'];
            $postal = $chosen['postal'];
        }

        $streets = [
            'Oak Ridge Dr', 'Maple Grove Ln', 'Cedar Elm St', 'Bluebonnet Trl',
            'Congress Ave', 'Pecan Grove Rd', 'Shoal Creek Blvd', 'Red Bud Ln',
            'Wildflower Way', 'Live Oak Pkwy', 'Sycamore Bend', 'Mesquite Ct',
        ];

        return [
            'address' => sprintf(
                '%d %s, %s, TX %s',
                mt_rand(100, 9899),
                $this->pick($streets),
                $city,
                $postal,
            ),
            'city' => $city,
        ];
    }

    /** See the class docblock: no synthetic SSNs, by design. */
    public function guardianDocumentNumber(): ?string
    {
        return null;
    }

    public function banks(): array
    {
        return [
            'Chase', 'Bank of America', 'Wells Fargo', 'Frost Bank',
            'USAA', 'Capital One', 'Charles Schwab Bank',
        ];
    }

    /**
     * US bank account: a 9-digit routing number followed by an account
     * number. The routing prefix is kept in the 07x range, which is not
     * an issued Federal Reserve district prefix, so these cannot collide
     * with a real routable ABA number.
     */
    public function bankAccountNumber(): string
    {
        return sprintf(
            '07%07d / %010d',
            mt_rand(0, 9_999_999),
            mt_rand(1, 9_999_999_99),
        );
    }

    /**
     * A subdomain of a domain the operator actually controls, not a .edu.
     *
     * Two reasons this is not lincoln.kynexsolutions.com:
     *   1. .edu is restricted by EDUCAUSE to accredited US institutions. A US
     *      buyer may well know that, and a fake .edu in a sales demo reads as
     *      careless.
     *   2. MAIL_MAILER is configured for a live Resend key. If anything in the
     *      demo ever actually sends, addresses on an unowned domain hard-bounce
     *      and damage the sender reputation of the real account. Keeping demo
     *      addresses on a controlled domain contains that.
     */
    public function emailDomain(): string
    {
        return 'lincoln.kynexsolutions.com';
    }

    public function leadership(): array
    {
        return [
            'admin' => [
                'name' => 'Dana Whitfield',
                'designation' => 'Office Manager',
                'qualification' => 'M.B.A. Nonprofit Management',
                'employee_id' => 'EMP-001',
                'salary' => 6_800_00,
            ],
            'principal' => [
                'name' => 'Dr. Marcus Ellery',
                'designation' => 'Head of School',
                'qualification' => 'Ed.D. Educational Leadership',
                'employee_id' => 'EMP-002',
                'salary' => 12_500_00,
            ],
        ];
    }

    public function designations(): array
    {
        return [
            'Head of School' => 'Administration',
            'Assistant Principal' => 'Administration',
            'Office Manager' => 'Administration',
            'Business Manager' => 'Finance',
            'Department Chair' => 'Academics',
            'Teacher' => 'Academics',
            'School Counselor' => 'Academics',
            'Media Specialist' => 'Library & Resources',
            'Registrar' => 'Support',
            'Campus Safety Officer' => 'Support',
            'Bus Driver' => 'Support',
        ];
    }

    /**
     * Amounts are USD cents. The schema's *_paisas columns simply store
     * minor currency units, so cents drop straight in.
     */
    public function salaryComponents(): array
    {
        return [
            ['Base Salary', 'earning', 'fixed', 0, false],
            ['Health Insurance Stipend', 'allowance', 'fixed', 45_000, false],  // $450/mo
            ['Retirement Match (403b)', 'allowance', 'percentage', 5, false],
            ['Professional Development', 'allowance', 'fixed', 15_000, false],  // $150/mo
            ['Federal Tax Withholding', 'deduction', 'percentage', 12, true],
        ];
    }

    /**
     * Monthly salaries in cents. Figures sit in a realistic band for an
     * independent day school in central Texas.
     */
    public function staffRoster(): array
    {
        return [
            // [authRole, designation, label, qualification, salaryCents, name, subject?]
            ['REGISTRAR', 'Assistant Principal', 'vice-principal', 'M.Ed Educational Leadership', 8_200_00, 'Karen Delgado'],
            ['ACCOUNTANT', 'Business Manager', 'accountant', 'B.S. Accounting, CPA', 7_400_00, 'Terrence Boyd'],

            ['TEACHER', 'Department Chair', 'teacher_math', 'M.S. Mathematics, TX Certified', 6_900_00, 'Alan Whitmore', 'Mathematics'],
            ['TEACHER', 'Department Chair', 'teacher_english', 'M.A. English Literature', 6_700_00, 'Priya Raghavan', 'English Language Arts'],
            ['TEACHER', 'Teacher', 'teacher_science', 'M.S. Biology, TX Certified', 6_500_00, 'Nicole Barrett', 'Biology'],
            ['TEACHER', 'Teacher', 'teacher_physics', 'M.S. Physics', 6_600_00, 'Daniel Okonkwo', 'Physics'],
            ['TEACHER', 'Teacher', 'teacher_social', 'M.A. American History', 6_200_00, 'Gregory Hansen', 'U.S. History'],
            ['TEACHER', 'Teacher', 'teacher_spanish', 'B.A. Spanish, ACTFL Certified', 5_900_00, 'Lucia Morales', 'Spanish'],
            ['TEACHER', 'Teacher', 'teacher_cs', 'B.S. Computer Science', 7_100_00, 'Wesley Kim', 'Computer Science'],
            ['TEACHER', 'Teacher', 'teacher_arts', 'M.F.A. Studio Art', 5_600_00, 'Rebecca Lindqvist', 'Visual Arts'],
            ['TEACHER', 'Teacher', 'teacher_pe', 'B.S. Kinesiology', 5_500_00, 'Marcus Doyle', 'Physical Education'],
            ['TEACHER', 'School Counselor', 'teacher_counselor', 'M.Ed School Counseling, LPC', 6_400_00, 'Yvonne Brooks', 'Advisory'],

            ['ATTENDANCE_CLERK', 'Registrar', 'clerk', 'B.A. Business Administration', 4_300_00, 'Sandra Pham'],
            ['LIBRARIAN', 'Media Specialist', 'librarian', 'M.L.I.S.', 5_200_00, 'Harold Nguyen'],
            // Safety officer and driver have NO auth role (no portal access).
            [null, 'Campus Safety Officer', 'gatekeeper', 'TX Guard License', 3_800_00, 'Ray Callahan'],
            [null, 'Bus Driver', 'driver', 'TX CDL Class B, S endorsement', 3_600_00, 'Eddie Vasquez'],
        ];
    }

    public function cms(): array
    {
        return [
            'founded_year' => 1998,
            'grade_range' => 'Kindergarten through Grade 12',
            'about_tagline' => "Austin's small-by-design K-12 academy since 1998",
            'office_hours' => 'Front office: Monday to Friday, 7:45 AM to 4:00 PM',
            'about' => "Lincoln Heights Academy opened its doors in Austin in 1998 with a simple conviction: a school should know every child by name. Nearly three decades later we remain deliberately mid-sized, serving families from Kindergarten through Grade 12 with a faculty of sixteen educators and staff.\n\nOur program pairs a college-preparatory core with genuine breadth — laboratory science, computer science, studio art, Spanish, and competitive athletics. Small sections mean a teacher notices when a student is thriving and, just as importantly, when they are not.",
            'vision' => 'To be the school Austin families choose when they want their child both academically stretched and personally known.',
            'mission' => 'We prepare students for college and for citizenship. Through small classes, invested teachers, and a close partnership with families, every graduate leaves able to think rigorously, write clearly, and act with integrity.',
            'principal_message' => "Thank you for your interest in Lincoln Heights Academy. What strikes visitors first is usually the quiet — not silence, but the sound of students genuinely at work. We keep our sections small on purpose, because the relationship between a teacher and a student is the part of education that cannot be automated. Come walk the halls with me and meet the people who make this place work.",
            'facilities' => [
                ['name' => 'Science Laboratories', 'description' => 'Dedicated biology and physics labs with equipment for AP-level coursework.'],
                ['name' => 'Computer Science Studio', 'description' => '28 workstations, a hardware bench, and a 3D printer for capstone projects.'],
                ['name' => 'Library & Media Center', 'description' => 'Roughly 12,000 volumes plus database access and quiet study carrels.'],
                ['name' => 'Athletics Complex', 'description' => 'Regulation gymnasium, turf field, and outdoor track for eleven varsity teams.'],
                ['name' => 'Visual Arts Studio', 'description' => 'Painting, ceramics, and a darkroom for the photography elective.'],
                ['name' => 'Performing Arts Hall', 'description' => '320-seat auditorium hosting choir, drama, and the spring musical.'],
                ['name' => 'Bus Service', 'description' => 'Four routes covering north Austin, Round Rock, and Cedar Park.'],
                ['name' => 'Dining Hall', 'description' => 'Scratch-cooked lunches with allergen-aware menus posted weekly.'],
            ],
            'testimonials' => [
                ['name' => 'Rachel Duncan', 'relation' => 'Parent of a Grade 7 student', 'message' => 'What sold us was the advisory system. Our son has one teacher who genuinely tracks how he is doing across every class, and we hear from her before we have to ask.'],
                ['name' => 'Andre Whitfield', 'relation' => 'Parent of a Grade 3 student', 'message' => 'Small classes are not marketing here. Fourteen kids in the room means our daughter gets called on, gets corrected, and gets known.'],
                ['name' => 'Michelle Tran', 'relation' => 'Parent of a Grade 11 student', 'message' => 'College counseling started in ninth grade, not senior year. The essay support alone was worth the tuition.'],
                ['name' => 'Dr. Paul Reinhardt', 'relation' => 'Alumni parent', 'message' => 'Both of ours graduated from Lincoln Heights and went on to strong programs. They arrived at college already able to write and argue.'],
            ],
            'stats' => [
                'students' => 100, 'teachers' => 16, 'established' => 1998,
                'pass_rate_percent' => 99, 'class_levels' => 13,
            ],
            'exam_highlights' => [
                ['exam' => 'Fall Semester Finals 2025', 'top_class' => 'Grade 12', 'top_percent' => 95.4],
                ['exam' => 'Fall Semester Finals 2025', 'top_class' => 'Grade 10', 'top_percent' => 93.7],
                ['exam' => 'Winter Benchmark 2026', 'top_class' => 'Grade 8', 'top_percent' => 92.1],
            ],
            'admission_steps' => [
                ['title' => 'Inquiry & Tour', 'description' => 'Submit the online inquiry form and schedule a campus visit with admissions.'],
                ['title' => 'Application & Records', 'description' => 'Complete the application and release your current school transcript.'],
                ['title' => 'Student Visit Day', 'description' => 'Applicants shadow a current student for a morning and meet a teacher.'],
                ['title' => 'Decision & Enrollment', 'description' => 'Receive the decision, review any tuition assistance award, and sign the contract.'],
            ],
            'hero_image' => 'https://placehold.co/1600x600/0f3d2e/ffffff?text=Lincoln+Heights+Academy',
            'about_image' => 'https://placehold.co/800x600/0f3d2e/ffffff?text=Our+Campus',
            'why_choose_us' => [
                ['title' => 'Small Class Sizes', 'icon' => 'user-group', 'description' => 'Sections capped in the low teens, so every student is known and accountable.'],
                ['title' => 'College Preparatory', 'icon' => 'academic-cap', 'description' => 'Honors and AP pathways with dedicated college counseling from Grade 9.'],
                ['title' => 'STEM Facilities', 'icon' => 'beaker', 'description' => 'Dedicated biology and physics labs plus a computer science studio.'],
                ['title' => 'Arts & Athletics', 'icon' => 'sparkles', 'description' => 'Studio art, choir, and eleven interscholastic teams across three seasons.'],
                ['title' => 'Family Portal', 'icon' => 'device-phone-mobile', 'description' => 'Live access to grades, attendance, assignments, and tuition balances.'],
                ['title' => 'Tuition Assistance', 'icon' => 'currency-dollar', 'description' => 'Need-based aid and multi-child discounts reviewed every admissions cycle.'],
            ],
        ];
    }

    /** Kindergarten plus Grades 1-12: 13 levels, keyed 0..12. */
    public function gradeLevels(): array
    {
        $levels = [0 => 'Kindergarten'];
        for ($n = 1; $n <= 12; $n++) {
            $levels[$n] = "Grade {$n}";
        }

        return $levels;
    }

    /** Two sections through Grade 5, single sections above. */
    public function sectionsForLevel(int $level): array
    {
        return $level <= 5 ? ['A', 'B'] : ['A'];
    }

    public function classSubjects(): array
    {
        $lower  = ['English Language Arts', 'Mathematics', 'Science', 'Visual Arts', 'Physical Education'];
        $inter  = ['English Language Arts', 'Mathematics', 'Science', 'U.S. History', 'Spanish', 'Visual Arts', 'Physical Education'];
        $middle = ['English Language Arts', 'Mathematics', 'Science', 'U.S. History', 'Spanish', 'Computer Science', 'Physical Education'];
        $upper  = ['English Language Arts', 'Mathematics', 'Biology', 'Physics', 'U.S. History', 'Spanish', 'Computer Science', 'Visual Arts', 'Physical Education'];

        $map = [];
        foreach ([0, 1, 2] as $n)          { $map[$n] = $lower; }
        foreach ([3, 4, 5] as $n)          { $map[$n] = $inter; }
        foreach ([6, 7, 8] as $n)          { $map[$n] = $middle; }
        foreach ([9, 10, 11, 12] as $n)    { $map[$n] = $upper; }

        return $map;
    }

    /**
     * Every subject resolves to a real teacher from staffRoster(). General
     * "Science" in the lower grades is taught by the biology teacher, which
     * is how a school this size actually staffs it.
     */
    public function subjectTeacherLabels(): array
    {
        return [
            'English Language Arts' => 'subject:English Language Arts',
            'Mathematics' => 'subject:Mathematics',
            'Science' => 'subject:Biology',
            'Biology' => 'subject:Biology',
            'Physics' => 'subject:Physics',
            'U.S. History' => 'subject:U.S. History',
            'Spanish' => 'subject:Spanish',
            'Computer Science' => 'subject:Computer Science',
            'Visual Arts' => 'subject:Visual Arts',
            'Physical Education' => 'subject:Physical Education',
            'Advisory' => 'subject:Advisory',
        ];
    }

    /**
     * 60 students across 13 grade levels — the "small and pristine" shape:
     * every record is inspectable during a live demo without hunting.
     */
    public function classSizes(): array
    {
        return [
            0 => 5, 1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5,
            6 => 4, 7 => 4, 8 => 4, 9 => 4, 10 => 4,
            11 => 5, 12 => 5,
        ];
    }

    public function subjects(): array
    {
        return [
            ['English Language Arts', 'ELA', '#3b82f6'],
            ['Mathematics', 'MATH', '#ef4444'],
            ['Science', 'SCI', '#f59e0b'],
            ['Biology', 'BIO', '#22c55e'],
            ['Physics', 'PHYS', '#8b5cf6'],
            ['U.S. History', 'USH', '#a855f7'],
            ['Spanish', 'SPAN', '#10b981'],
            ['Computer Science', 'CSCI', '#0ea5e9'],
            ['Visual Arts', 'ART', '#f43f5e'],
            ['Physical Education', 'PE', '#84cc16'],
            ['Advisory', 'ADV', '#64748b'],
        ];
    }

    public function sliders(): array
    {
        return [
            ['Learn Boldly. Lead Kindly.', "Austin's small-by-design K-12 academy since 1998", 'Apply Now', '/apply'],
            ['Sections in the Low Teens', 'Every student known by name, every day', 'Take a Tour', '/about'],
            ['College Counseling from Grade 9', 'Not senior year — four years of guidance', 'Learn More', '/academics'],
            ['Admissions Open', 'Kindergarten through Grade 12 — rolling review', 'Apply Today', '/apply'],
        ];
    }

    /**
     * Standard US letter scale on a 4.0 GPA. Note the US convention differs
     * from the Pakistani one the seeder shipped with: 90+ is a plain A (there
     * is no A+ band at 90), and the pass floor is 60 rather than 40.
     */
    public function gradeRules(): array
    {
        return [
            ['A', 93, 100, 4.0, 'Excellent'],
            ['A-', 90, 92, 3.7, 'Excellent'],
            ['B+', 87, 89, 3.3, 'Very Good'],
            ['B', 83, 86, 3.0, 'Good'],
            ['B-', 80, 82, 2.7, 'Good'],
            ['C+', 77, 79, 2.3, 'Satisfactory'],
            ['C', 73, 76, 2.0, 'Satisfactory'],
            ['C-', 70, 72, 1.7, 'Passing'],
            ['D', 60, 69, 1.0, 'Needs Improvement'],
            ['F', 0, 59, 0.0, 'Failing'],
        ];
    }

    /** Monthly recurring costs, in USD. */
    public function recurringExpenses(): array
    {
        return [
            ['Utilities', 'Electricity (Austin Energy)', 2_800, 3_600],
            ['Utilities', 'Water and wastewater', 420, 640],
            ['Utilities', 'Natural gas', 180, 520],
            ['Internet & IT', 'Fiber internet and phone (business)', 640, 720],
            ['Internet & IT', 'SIS and email subscriptions', 890, 950],
            ['Rent', 'Campus facility lease', 18_500, 18_500],
        ];
    }

    /** Dated one-off purchases across the spring semester, in USD. */
    public function periodicExpenses(): array
    {
        return [
            ['Stationery', 'Classroom supplies restock', 1_200, 2_100, '2026-02-15'],
            ['Stationery', 'Dry-erase markers and easel pads', 320, 480, '2026-04-04'],
            ['Stationery', 'Copy paper (40 cases)', 700, 980, '2026-03-08'],
            ['Lab Supplies', 'Chemistry reagents restock', 1_650, 2_600, '2026-03-12'],
            ['Lab Supplies', 'Biology dissection specimens', 840, 1_320, '2026-04-05'],
            ['Sports Equipment', 'Basketball and volleyball kit', 2_100, 3_400, '2026-02-20'],
            ['Sports Equipment', 'Track uniforms (spring season)', 1_400, 2_200, '2026-03-01'],
            ['Library Books', 'Collection refresh and database renewal', 2_800, 4_200, '2026-02-08'],
            ['Repairs & Maintenance', 'HVAC servicing, upper school wing', 1_500, 2_300, '2026-04-12'],
            ['Repairs & Maintenance', 'Gym floor refinishing', 3_100, 4_800, '2026-03-22'],
            ['Professional Development', 'AP summer institute registrations', 1_600, 2_400, '2026-04-18'],
            ['Exam Printing', 'Fall semester final exam printing', 1_150, 1_850, '2026-02-05'],
            ['Exam Printing', 'Spring benchmark printing', 1_150, 1_850, '2026-04-10'],
        ];
    }

    public function feeGroups(): array
    {
        return [
            'Tuition' => 'Monthly tuition, billed over ten months',
            'Enrollment' => 'Registration and one-time enrollment charges',
            'Assessments' => 'Standardised testing and AP exam fees',
            'Optional Services' => 'Bus service, athletics, activity fees',
        ];
    }

    public function feeTypes(): array
    {
        return [
            ['Monthly Tuition', 'Tuition', true],
            ['Registration Fee', 'Enrollment', false],
            ['Technology Fee', 'Enrollment', false],
            ['Science Lab Fee', 'Optional Services', true],
            ['Library & Media Fee', 'Optional Services', true],
            ['Athletics Fee', 'Optional Services', true],
            ['Computer Science Lab', 'Optional Services', true],
            ['Bus Service', 'Optional Services', true],
            ['AP & Assessment Fees', 'Assessments', false],
            ['Books & Supplies', 'Enrollment', false],
        ];
    }

    /**
     * Amounts in USD. Tuition lands around $8.5k-$11.5k a year across ten
     * monthly instalments, which is the realistic band for an independent day
     * school in central Texas.
     */
    public function feeRates(): array
    {
        return [
            'Monthly Tuition' => ['lower' => 850, 'middle' => 975, 'upper' => 1150],
            'Registration Fee' => ['lower' => 350, 'middle' => 400, 'upper' => 450],
            'Technology Fee' => ['lower' => 180, 'middle' => 240, 'upper' => 300],
            'Library & Media Fee' => ['lower' => 40, 'middle' => 50, 'upper' => 60],
            'Athletics Fee' => ['lower' => 60, 'middle' => 110, 'upper' => 160],
            'Science Lab Fee' => ['lower' => 0, 'middle' => 70, 'upper' => 130],
            'Computer Science Lab' => ['lower' => 0, 'middle' => 55, 'upper' => 85],
            'Bus Service' => ['lower' => 185, 'middle' => 185, 'upper' => 185],
            'AP & Assessment Fees' => ['lower' => 0, 'middle' => 45, 'upper' => 105],
            'Books & Supplies' => ['lower' => 95, 'middle' => 120, 'upper' => 145],
        ];
    }

    /** Lower school K-5, middle school 6-8, upper school 9-12. */
    public function feeTierFor(int $level): string
    {
        return $level <= 5 ? 'lower' : ($level <= 8 ? 'middle' : 'upper');
    }

    public function feeRoles(): array
    {
        return [
            'tuition' => 'Monthly Tuition',
            'admission' => 'Registration Fee',
            'transport' => 'Bus Service',
            'recurring' => [
                'Monthly Tuition', 'Library & Media Fee', 'Athletics Fee',
                'Science Lab Fee', 'Computer Science Lab',
            ],
        ];
    }

    /**
     * Verified lecture catalogue. Every video id below was confirmed live via
     * https://www.youtube.com/oembed before being committed.
     */
    public function lectures(): array
    {
        return [
            // ── Biology (Grade 9) ──────────────────────────────────
            [9, 'Biology', 'Photosynthesis: how plants build sugar from light', '8rbr3lRLNmY',
                "Photosynthesis is how a plant turns light energy into chemical energy it can store and use.\n\nInputs and outputs. The plant takes in carbon dioxide through pores in its leaves called stomata, and water through its roots. Using light, it produces glucose (a sugar) and releases oxygen as a by-product. The oxygen we breathe is largely a waste product of this process.\n\nWhere it happens. Inside leaf cells are organelles called chloroplasts, which contain the green pigment chlorophyll. Chlorophyll absorbs light strongly in the blue and red parts of the spectrum and reflects green, which is why healthy leaves look green.\n\nTwo stages. The light-dependent reactions capture light energy and use it to split water, releasing oxygen and storing energy in carrier molecules. The light-independent reactions (the Calvin cycle) then use that stored energy to assemble carbon dioxide into glucose. The second stage needs the products of the first, which is why a plant cannot build sugar in the dark indefinitely.\n\nWhy it matters. Photosynthesis is the entry point for almost all energy in living systems. Every food chain traces back to an organism that captured sunlight this way."],

            [9, 'Biology', 'Inside the light reactions', 'GR2GA7chA_c',
                "This lecture goes one level deeper into the first stage of photosynthesis.\n\nThe light reactions happen in the thylakoid membranes inside the chloroplast. Light strikes chlorophyll and excites electrons to a higher energy state. Those energised electrons are passed along a chain of proteins, and the energy released as they move is used to pump hydrogen ions across the membrane.\n\nSplitting water. The electrons that chlorophyll loses have to be replaced. They come from water molecules, which are split apart — this is the step that releases oxygen gas.\n\nWhat gets made. The output of this stage is not sugar. It is two energy-carrying molecules, ATP and NADPH, which the Calvin cycle consumes in the next stage. Keeping this distinction clear is the single most common source of confusion about photosynthesis: the light reactions make the *fuel*, the Calvin cycle makes the *sugar*."],

            [7, 'Science', 'Photosynthesis and the flow of energy in ecosystems', '_1U6uMmUJZU',
                "This lecture connects photosynthesis to the bigger picture of how energy moves through an ecosystem.\n\nProducers. Plants, algae and some bacteria are producers: they make their own food from sunlight. Everything else is a consumer, eating producers or eating other consumers.\n\nEnergy flows one way. Energy enters an ecosystem as sunlight, is captured by producers, and passes up through consumers. At each step most of the energy is lost as heat, which is why food chains are short and why there are far fewer top predators than plants.\n\nMatter cycles. Unlike energy, matter is reused. Carbon moves from the air into plants, into animals that eat them, and back to the air through respiration and decay. Photosynthesis and respiration are near-opposites: one stores energy in sugar and releases oxygen, the other releases that energy and consumes oxygen."],

            // ── Mathematics ────────────────────────────────────────
            [8, 'Mathematics', 'Variables, expressions and equations', 'vDqOoI-4Z6M',
                "This lecture sets up the vocabulary the rest of algebra depends on.\n\nA variable is a letter standing in for a number we do not know yet, or one that can change. An expression is a combination of numbers, variables and operations with no equals sign, such as 3x + 5. An equation states that two expressions are equal, such as 3x + 5 = 20.\n\nWhy the distinction matters. You evaluate an expression (put a number in, get a number out). You solve an equation (find the value or values of the variable that make the statement true). Students who blur the two get stuck later, because 'simplify' and 'solve' are different instructions.\n\nA useful mental model: an equation is a balance scale. Whatever you do to one side you must do to the other, or the balance breaks."],

            [8, 'Mathematics', 'Solving linear equations, step by step', 'bAerID24QJ0',
                "A linear equation is one where the variable appears only to the first power — no squares, no variables in denominators.\n\nThe goal. Isolate the variable on one side. You do this by undoing operations in reverse order, always applying the same operation to both sides.\n\nWorked pattern. To solve 3x + 5 = 20: subtract 5 from both sides to get 3x = 15, then divide both sides by 3 to get x = 5.\n\nChecking. Substitute your answer back into the original equation. 3(5) + 5 = 20, which is true, so x = 5 is correct. This check costs seconds and catches most arithmetic slips.\n\nCommon errors. Applying an operation to only one side; losing a negative sign when subtracting; dividing only part of a side."],

            [9, 'Mathematics', 'Equations with variables on both sides', 'f15zA0PhSek',
                "This lecture extends equation-solving to the case where the unknown appears on both sides.\n\nStrategy. Collect the variable terms on one side and the constant terms on the other, then finish as with any linear equation.\n\nWorked pattern. For 5x + 3 = 2x + 18: subtract 2x from both sides to get 3x + 3 = 18, subtract 3 to get 3x = 15, divide by 3 to get x = 5.\n\nWhich side. It does not matter mathematically which side you gather the variables on, but moving the smaller coefficient avoids negative numbers and reduces mistakes.\n\nTwo special cases worth recognising. If the variables cancel and you are left with something true (like 7 = 7), every number is a solution. If you are left with something false (like 7 = 4), there is no solution at all."],

            [11, 'Mathematics', 'Completing the square', 'KouDAzYl_bc',
                "Completing the square rewrites a quadratic so the variable appears once, inside a squared bracket, which makes it solvable directly.\n\nThe idea. x² + bx can always be turned into a perfect square by adding (b/2)². Because you cannot add something to one side for free, you either subtract it again or add it to the other side.\n\nWorked pattern. For x² + 6x + 5 = 0: move the constant to get x² + 6x = -5. Half of 6 is 3, and 3² is 9, so add 9 to both sides: x² + 6x + 9 = 4. The left side is now (x + 3)², so (x + 3)² = 4, giving x + 3 = ±2 and therefore x = -1 or x = -5.\n\nWhy learn it. It always works, even when factoring fails, it is where the quadratic formula comes from, and it is the method used to convert a quadratic into vertex form."],

            // ── Physics (Grade 10) ─────────────────────────────────
            [10, 'Physics', "Newton's first law: inertia", '5-ZFOhHQS68',
                "Newton's first law says an object at rest stays at rest, and an object in motion continues at constant velocity, unless a net force acts on it.\n\nInertia is the name for this resistance to change in motion. Mass is the measure of it: a loaded trolley is harder to start and harder to stop than an empty one.\n\nThe counter-intuitive part. Everyday experience suggests moving things naturally slow down, so force must be needed to keep them moving. They slow because of friction and air resistance — forces we do not see. Remove them, as in space, and motion continues indefinitely.\n\nNet force is what matters. Forces can act without changing motion, provided they cancel. A book resting on a table has gravity pulling down and the table pushing up in equal measure; the net force is zero, so it stays put."],

            [10, 'Physics', "Newton's third law: action and reaction", 'UD-nc50M-I0',
                "Newton's third law says that when object A exerts a force on object B, B exerts an equal and opposite force on A.\n\nThe pair is the key idea. These two forces always act on *different* objects. That is why they do not cancel each other out — cancelling only happens between forces acting on the same object.\n\nWorked example. When you push off a wall on a skateboard, you push the wall backwards and the wall pushes you forwards with equal force. You accelerate and the wall does not, because the wall is attached to a building with enormous mass.\n\nA rocket works the same way. It pushes exhaust gas downwards, and the gas pushes the rocket upwards. It does not need air to push against, which is why rockets work in vacuum.\n\nCommon error. Saying the forces 'cancel so nothing moves'. Always identify which object each force acts on before deciding what cancels."],

            // ── U.S. History (Grade 8) ─────────────────────────────
            [8, 'U.S. History', 'Taxes, smuggling and the road to revolution', 'Eytc9ZaNWyc',
                "This lecture covers why relations between Britain and its American colonies broke down in the 1760s and 1770s.\n\nThe financial background. Britain finished the Seven Years' War with heavy debt and a larger North American territory to defend. Parliament looked to the colonies to contribute, through measures such as the Stamp Act and the Townshend duties.\n\nThe colonial objection. The dispute was less about the amount than about who had the right to levy it. Colonists had no members in Parliament, which produced the argument summarised as 'no taxation without representation'.\n\nSmuggling and enforcement. Colonial merchants had long evaded trade rules. Tighter enforcement, including searches under general warrants, turned a commercial irritation into a constitutional grievance about rights.\n\nEscalation. Boycotts, the Boston Massacre, and the destruction of tea in Boston Harbour each hardened both sides, and Britain's punitive response pushed previously separate colonies toward common action."],

            [8, 'U.S. History', 'Who actually won the American Revolution?', '3EiSymRrKI4',
                "This lecture asks a sharper question than 'who won the war': who gained from the outcome, and who did not.\n\nThe military outcome. The colonies secured independence, with decisive French support in money, troops and naval power. Britain lost the war but not its empire or its economy.\n\nWho gained. Propertied white men gained self-government and political office. Many ordinary soldiers and farmers gained far less than they had been led to expect.\n\nWho did not. Slavery survived the Revolution and expanded afterwards, despite revolutionary language about liberty. Native nations largely lost, as independence removed the British restraint on westward settlement. Loyalists lost property and often left. Women's legal position changed little.\n\nThe historical point. 'Revolution' describes the change in who governed more accurately than a change in who held social or economic power. Asking who benefited is how historians test that distinction."],

            // ── Grade 12 (senior year) ─────────────────────────────
            [12, 'Mathematics', 'Solving equations by graphing', '573yqfOoMwE',
                "This lecture connects algebra to geometry: the solutions of an equation are the points where graphs meet.\n\nThe idea. To solve f(x) = g(x), graph both sides as separate functions. Wherever the two curves intersect, the x-value is a solution, because at that point the two expressions have the same value.\n\nWhy it is useful. Some equations cannot be solved neatly by algebra at all. Graphing gives you the number of solutions and good approximations even when an exact form is out of reach.\n\nReading the picture. Two intersections means two solutions. One means a single (possibly repeated) solution. No intersection means no real solution — which is a genuine answer, not a failure.\n\nA caution. Graphing shows you approximately where solutions are. If the question asks for an exact value, use it to check your algebra rather than to replace it."],

            [12, 'Mathematics', 'Rational equations', '3RdNPrNUi4s',
                "A rational equation contains a variable in a denominator, which introduces a complication no other equation type has.\n\nMethod. Multiply every term by the least common denominator to clear the fractions, then solve the polynomial equation that remains.\n\nThe complication. Multiplying by an expression containing the variable can introduce solutions that do not satisfy the original equation. These are called extraneous solutions.\n\nWhy they appear. The original equation is undefined wherever a denominator equals zero. The cleared equation has no such restriction, so it can produce a value the original forbids.\n\nWhat to do. Before solving, note which values make any denominator zero. After solving, discard any answer on that list. Checking is not optional here — it is part of the method."],

            [12, 'Physics', "Newton's third law in practice", 'VfpKzwrhmqQ',
                "This lecture works through the situations where the third law is most often misapplied.\n\nThe rule. Forces come in pairs, equal in size, opposite in direction, and acting on two *different* objects.\n\nWalking. You push backwards on the ground; the ground pushes you forwards. Without friction there is nothing to push against, which is why walking on ice fails.\n\nThe horse-and-cart puzzle. If the cart pulls back on the horse as hard as the horse pulls the cart, why does anything move? Because those two forces act on different objects, so they never cancel. What moves the system is the horse pushing against the ground.\n\nA weight on a table. Gravity pulls the book down and the table pushes up. These are equal and opposite, but they are *not* a third-law pair — both act on the book. The book's third-law partner to gravity is the book pulling up on the Earth.\n\nThe test: name both objects for every force. If two forces act on the same object, they are not a third-law pair."],

            [12, 'U.S. History', 'The Constitution, the Articles and federalism', 'bO7FQsCcbD8',
                "This lecture covers how the United States moved from its first constitution to its second.\n\nThe Articles of Confederation. The first framework deliberately kept central power weak. Congress could not levy taxes or regulate commerce directly, and had no executive or federal judiciary. States behaved much like separate countries.\n\nWhy it failed. Without taxing power the national government could not pay war debts. Trade disputes between states went unresolved. Shays' Rebellion suggested the government could not maintain order.\n\nThe Constitutional Convention. Delegates met in 1787 nominally to revise the Articles and instead replaced them. The central compromises balanced large against small states (a bicameral legislature) and, less defensibly, made concessions over slavery.\n\nFederalism. Power is divided between national and state governments, with some powers shared. That division is not settled once and for all — the boundary has been contested throughout American history and still is.\n\nRatification. Federalists argued for the stronger union; Anti-Federalists feared concentrated power. The Bill of Rights was the price of ratification."],

            [12, 'Biology', 'Breaking down the stages of photosynthesis', 'Wt5EMpUt-_g',
                "A senior-level treatment of how the two stages of photosynthesis fit together.\n\nStage one, in the thylakoid membrane. Light excites electrons in chlorophyll. As those electrons move down an electron transport chain, the energy released pumps hydrogen ions across the membrane, building up a gradient. Ions flowing back through an enzyme drive the synthesis of ATP. Water is split to replace the lost electrons, releasing oxygen.\n\nStage two, in the stroma. The Calvin cycle uses that ATP and NADPH to fix carbon dioxide. An enzyme attaches CO2 to a five-carbon sugar; the resulting molecules are reduced and rearranged, some leaving as usable sugar and the rest regenerating the starting molecule so the cycle continues.\n\nThe dependency. Stage two consumes exactly what stage one produces. This is why it is called light-*independent* rather than 'dark' — it does not need light directly, but it cannot run for long without the products of a stage that does.\n\nWhere it can bottleneck. Limited light, limited CO2, or temperatures that impair enzymes will each cap the overall rate."],

            // ── Middle school coverage ─────────────────────────────
            [6, 'Science', "Newton's first law for middle school", 'Q0T2zjmvvA0',
                "Things keep doing what they are already doing unless something makes them change. That is Newton's first law.\n\nAt rest stays at rest. A ball on the floor will not start rolling by itself. Something has to push it.\n\nIn motion stays in motion. A ball rolling on a smooth floor keeps going, and slows only because friction and air push against it. On very smooth ice it travels much further, and in space it would keep going indefinitely.\n\nInertia. This tendency to resist change is called inertia, and heavier objects have more of it. A shopping trolley full of groceries is harder to start moving, and harder to stop, than an empty one.\n\nEveryday evidence. When a bus brakes suddenly you keep moving forwards — your body was in motion and nothing stopped it until the seatbelt did. This is exactly why seatbelts exist."],

            [6, 'U.S. History', 'The Seven Years War and the Great Awakening', '5vKGU3aEGss',
                "This lecture covers two developments that shaped the American colonies before the Revolution.\n\nThe Seven Years War. Britain and France fought over territory in North America and elsewhere. Britain won and gained enormous territory, but finished deeply in debt — debt it then tried to recover partly from the colonies.\n\nAn unintended effect. Colonial militias fought alongside British regulars and came away less impressed by them, and colonists from different colonies worked together for the first time. Both mattered later.\n\nThe Great Awakening. A wave of religious revival spread through the colonies, emphasising personal conviction over inherited authority. Preachers drew large crowds across colonial boundaries.\n\nWhy a history course pairs them. Both encouraged colonists to question established authority and to see themselves as connected to people in other colonies. Neither caused the Revolution, but both helped make a shared colonial identity possible."],
            // ── Computer Science (Grade 11) ────────────────────────
            [11, 'Computer Science', 'What is an algorithm?', 'rL8X2mlNHPM',
                "An algorithm is a finite, unambiguous sequence of steps that solves a problem or completes a task.\n\nWhat makes it an algorithm. It must terminate, each step must be clearly defined, and it must produce a correct result for every valid input — not just the one you tested.\n\nWhy efficiency matters. Two algorithms can both be correct while differing enormously in cost. Searching a sorted list item by item takes time proportional to the list length; binary search repeatedly halves the range and takes time proportional to the logarithm of the length. For a million items that is roughly a million steps versus about twenty.\n\nHow we compare them. We describe how running time grows as input grows, rather than measuring seconds, because seconds depend on the machine. This is what Big-O notation expresses.\n\nThe practical lesson: choosing a better algorithm usually beats buying a faster computer."],

            [11, 'Computer Science', 'Data structures: choosing how to store data', 'DuDz6B4cqVc',
                "A data structure is a way of organising data so the operations you need are fast.\n\nArrays store items in consecutive memory, so reading the nth item is immediate, but inserting in the middle means shifting everything after it.\n\nLinked lists store each item with a pointer to the next, so inserting is cheap once you are in position, but reaching the nth item means walking from the start.\n\nStacks are last-in-first-out — the structure behind undo history and function calls. Queues are first-in-first-out — the structure behind print jobs and task scheduling.\n\nTrees store data hierarchically. A balanced binary search tree keeps insertion and lookup fast by halving the search space at each step.\n\nThe central idea: there is no best structure, only the best fit for the operations your program performs most. Pick the structure after you know the access pattern."],
        ];
    }

    /**
     * Three online exams, one in each state, all with AI grading enabled.
     *
     * Questions are original and deliberately mixed: auto-gradable MCQ and
     * true/false alongside essay and open short-answer items that carry no
     * correct answer, which is what routes them to the AI grader.
     */
    public function onlineExams(): array
    {
        return [
            // ── Already sat and AI-graded: the "show me the output" exam ──
            [
                'level' => 12,
                'subject' => 'Biology',
                'name' => 'Photosynthesis — Unit Assessment',
                'state' => 'graded',
                'duration' => 40,
                'questions' => [
                    ['mcq', 'Which gas is released as a by-product of photosynthesis?',
                        ['Carbon dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'], 'Oxygen', 2,
                        'Water is split during the light reactions, releasing oxygen.'],
                    ['mcq', 'In which part of the chloroplast does the Calvin cycle take place?',
                        ['Thylakoid membrane', 'Stroma', 'Outer membrane', 'Nucleus'], 'Stroma', 2,
                        'The light reactions occur in the thylakoid membrane; the Calvin cycle occurs in the stroma.'],
                    ['true_false', 'The light-independent reactions can continue indefinitely in complete darkness.',
                        null, 'false', 2,
                        'They do not use light directly, but they consume ATP and NADPH produced by the light reactions, so they stall once those run out.'],
                    ['mcq', 'Chlorophyll appears green because it primarily:',
                        ['Absorbs green light', 'Reflects green light', 'Emits green light', 'Converts light to green pigment'],
                        'Reflects green light', 2,
                        'Chlorophyll absorbs strongly in the blue and red regions and reflects green.'],
                    // AI-graded: no correct_answer supplied
                    ['short_answer', 'Name the two stages of photosynthesis and state which one produces glucose.',
                        null, null, 4, null],
                    ['essay', 'Explain why the Calvin cycle depends on the light reactions. Refer to the specific molecules that pass between the two stages, and describe what would happen to the rate of glucose production if light were removed for several hours.',
                        null, null, 8, null],
                ],
            ],

            // ── Open now: sit it live during the demo ────────────────
            [
                'level' => 12,
                'subject' => 'Physics',
                'name' => "Newton's Laws — Open Assessment",
                'state' => 'open',
                'duration' => 30,
                'questions' => [
                    ['mcq', 'A book rests on a table. Which statement is correct?',
                        [
                            'No forces act on the book',
                            'Gravity acts on the book but the table exerts no force',
                            'Gravity and the table\'s upward force act on the book and sum to zero',
                            'The forces on the book are a Newton third-law pair',
                        ],
                        'Gravity and the table\'s upward force act on the book and sum to zero', 2,
                        'Both forces act on the same object, so they are not a third-law pair — they simply cancel.'],
                    ['true_false', 'An object moving at constant velocity has no net force acting on it.',
                        null, 'true', 2,
                        'Constant velocity means zero acceleration, which by the second law means zero net force.'],
                    ['mcq', 'Why can a rocket accelerate in the vacuum of space?',
                        [
                            'It pushes against the air',
                            'It pushes exhaust gas backwards and the gas pushes it forwards',
                            'Gravity pulls it forwards',
                            'It cannot accelerate in a vacuum',
                        ],
                        'It pushes exhaust gas backwards and the gas pushes it forwards', 2,
                        'Third-law pair between rocket and exhaust; no external medium is needed.'],
                    ['short_answer', 'A passenger lurches forward when a bus brakes suddenly. Explain this using inertia.',
                        null, null, 4, null],
                    ['essay', 'A student claims that in a tug of war the two teams pull on each other with equal and opposite force, so neither team can ever win. Identify the flaw in this reasoning and explain what actually determines the outcome.',
                        null, null, 8, null],
                ],
            ],

            // ── Scheduled: keeps the upcoming list populated ─────────
            [
                'level' => 12,
                'subject' => 'U.S. History',
                'name' => 'Constitution & Federalism — Semester Assessment',
                'state' => 'upcoming',
                'duration' => 50,
                'questions' => [
                    ['mcq', 'Which weakness of the Articles of Confederation most directly caused the national government\'s financial problems?',
                        [
                            'It had no national army',
                            'It could not levy taxes directly',
                            'It had no written bill of rights',
                            'It allowed only one house of Congress',
                        ],
                        'It could not levy taxes directly', 2,
                        'Without direct taxing power, Congress depended on requisitions the states often ignored.'],
                    ['true_false', 'The Bill of Rights was part of the Constitution as originally ratified in 1788.',
                        null, 'false', 2,
                        'It was added by amendment afterwards, largely as the price of Anti-Federalist support for ratification.'],
                    ['short_answer', 'Define federalism in one or two sentences, and give one example of a power shared by both national and state governments.',
                        null, null, 4, null],
                    ['essay', 'The Constitutional Convention was called to revise the Articles of Confederation and instead replaced them. Explain what the delegates concluded could not be fixed by revision, and assess one compromise they reached that you consider defensible and one you do not.',
                        null, null, 10, null],
                ],
            ],
        ];
    }

    /**
     * A year-round Texas calendar: early-July start, mid-June end.
     *
     * Year-round schooling with short intersessions is common in Texas, and
     * it is the framing that makes this demo honest. The alternative — a
     * conventional August start — would put today in the opening days of the
     * year, where every syllabus is 0% covered, no work has been marked and
     * the whole progress story has nothing to show. Here the school is around
     * six weeks in: two units taught, one running, the rest ahead.
     */
    public function academicYears(): array
    {
        return [
            ['name' => '2025-2026', 'start' => '2025-07-07', 'end' => '2026-06-12', 'is_current' => false],
            ['name' => '2026-2027', 'start' => '2026-07-06', 'end' => '2027-06-11', 'is_current' => true],
        ];
    }

    /**
     * Practice quizzes and flashcards, keyed by lecture video id.
     *
     * Written to test understanding rather than recall where possible: several
     * items present a common misconception as a distractor, because the
     * explanation is where the learning happens on a self-marking quiz.
     */
    public function lecturePractice(): array
    {
        return [
            // ── Biology: Photosynthesis — how plants build sugar (G9) ──
            '8rbr3lRLNmY' => [
                'quiz' => [
                    ['mcq', 'Where does the carbon in a plant\'s glucose originally come from?',
                        ['The soil', 'Water taken up by the roots', 'Carbon dioxide in the air', 'Chlorophyll'],
                        'Carbon dioxide in the air',
                        'A common misconception is that plants build mass from soil. The carbon comes from CO2 taken in through the stomata.'],
                    ['true_false', 'Oxygen released during photosynthesis comes from carbon dioxide.',
                        null, 'false',
                        'It comes from splitting water. The oxygen in CO2 ends up in the glucose, not in the released gas.'],
                    ['mcq', 'Why do most leaves look green?',
                        ['Chlorophyll absorbs green light', 'Chlorophyll reflects green light', 'Green light has the most energy', 'Leaves emit green light'],
                        'Chlorophyll reflects green light',
                        'Chlorophyll absorbs strongly in the blue and red bands. The green it does not absorb is reflected, which is what we see.'],
                ],
                'flashcards' => [
                    ['Photosynthesis — one sentence', 'The process by which a plant uses light energy to convert carbon dioxide and water into glucose, releasing oxygen.'],
                    ['Inputs and outputs', 'In: carbon dioxide, water, light. Out: glucose and oxygen.'],
                    ['Stomata', 'Tiny pores on a leaf that let carbon dioxide in and water vapour out.'],
                    ['Chloroplast', 'The organelle where photosynthesis happens; it contains chlorophyll.'],
                ],
            ],

            // ── Biology: the light reactions (G9) ──────────────────────
            'GR2GA7chA_c' => [
                'quiz' => [
                    ['mcq', 'What are the products of the light-dependent reactions?',
                        ['Glucose and oxygen', 'ATP, NADPH and oxygen', 'Carbon dioxide and water', 'Starch and ATP'],
                        'ATP, NADPH and oxygen',
                        'The light reactions make the energy carriers, not the sugar. Glucose is assembled later, in the Calvin cycle.'],
                    ['true_false', 'The electrons chlorophyll loses are replaced by splitting water.',
                        null, 'true',
                        'Water is split to resupply chlorophyll, and the oxygen released is the by-product of that step.'],
                    ['mcq', 'Where in the chloroplast do the light reactions occur?',
                        ['Stroma', 'Thylakoid membrane', 'Cell wall', 'Nucleus'],
                        'Thylakoid membrane',
                        'Light reactions happen in the thylakoid membrane; the Calvin cycle happens in the surrounding stroma.'],
                ],
                'flashcards' => [
                    ['ATP and NADPH', 'The two energy-carrying molecules made by the light reactions and spent by the Calvin cycle.'],
                    ['Why water is split', 'To replace the electrons chlorophyll loses when light excites them. Oxygen is the by-product.'],
                    ['Thylakoid vs stroma', 'Thylakoid membrane: light reactions. Stroma: Calvin cycle.'],
                    ['The most common mix-up', 'The light reactions make the fuel; the Calvin cycle makes the sugar.'],
                ],
            ],

            // ── Science: photosynthesis in ecosystems (G7) ─────────────
            '_1U6uMmUJZU' => [
                'quiz' => [
                    ['mcq', 'What is a producer?',
                        ['An animal that eats plants', 'An organism that makes its own food from sunlight', 'An organism that breaks down dead matter', 'The top predator in a food chain'],
                        'An organism that makes its own food from sunlight',
                        'Plants, algae and some bacteria are producers. Everything else is a consumer.'],
                    ['true_false', 'Energy cycles round an ecosystem the same way matter does.',
                        null, 'false',
                        'Matter cycles, but energy flows one way — in as sunlight, out as heat. That is why food chains are short.'],
                    ['mcq', 'Why are there usually far fewer top predators than plants?',
                        ['Predators reproduce slowly', 'Most energy is lost as heat at each step', 'Plants are easier to count', 'Predators need less energy'],
                        'Most energy is lost as heat at each step',
                        'Only a small fraction of energy passes to the next level, so each step supports fewer organisms.'],
                ],
                'flashcards' => [
                    ['Producer', 'An organism that makes its own food from sunlight — the entry point for energy into an ecosystem.'],
                    ['Consumer', 'An organism that gets energy by eating producers or other consumers.'],
                    ['Energy vs matter', 'Energy flows one way and is lost as heat. Matter cycles and is reused.'],
                    ['Respiration vs photosynthesis', 'Near-opposites: one stores energy in sugar and releases oxygen, the other releases that energy and uses oxygen.'],
                ],
            ],

            // ── Biology: breaking down the stages (G12) ────────────────
            'Wt5EMpUt-_g' => [
                'quiz' => [
                    ['mcq', 'What drives ATP synthesis in the light reactions?',
                        ['Direct absorption of light by ATP synthase', 'A hydrogen ion gradient across the thylakoid membrane', 'The splitting of glucose', 'Carbon fixation'],
                        'A hydrogen ion gradient across the thylakoid membrane',
                        'Electron transport pumps hydrogen ions across the membrane; their flow back through the enzyme drives ATP synthesis.'],
                    ['mcq', 'In the Calvin cycle, what happens to most of the molecules produced?',
                        ['They all leave as glucose', 'Most regenerate the starting five-carbon sugar', 'They are converted to oxygen', 'They are stored in the thylakoid'],
                        'Most regenerate the starting five-carbon sugar',
                        'Only some product leaves as usable sugar; the rest must regenerate the acceptor molecule so the cycle can continue.'],
                    ['true_false', '"Light-independent" means the Calvin cycle can run indefinitely without light.',
                        null, 'false',
                        'It does not use light directly, but it consumes ATP and NADPH from the light reactions, so it stalls once those run out.'],
                ],
                'flashcards' => [
                    ['Calvin cycle, in one line', 'Uses ATP and NADPH to fix carbon dioxide into sugar, in the stroma.'],
                    ['Why "light-independent" is misleading', 'It needs no light directly, but depends entirely on the products of a stage that does.'],
                    ['Three things that cap the rate', 'Available light, available carbon dioxide, and temperature affecting enzymes.'],
                    ['Carbon fixation', 'Attaching CO2 to a five-carbon sugar — the first step of the Calvin cycle.'],
                ],
            ],

            // ── Mathematics: variables, expressions, equations (G8) ────
            'vDqOoI-4Z6M' => [
                'quiz' => [
                    ['mcq', 'Which of these is an expression, not an equation?',
                        ['3x + 5 = 20', 'x = 4', '3x + 5', '2x - 1 = x + 3'],
                        '3x + 5',
                        'An equation contains an equals sign and states two things are equal. An expression does not.'],
                    ['mcq', 'What does it mean to SOLVE an equation?',
                        ['Simplify it as far as possible', 'Find the value(s) of the variable that make it true', 'Substitute a number and calculate', 'Rewrite it with fewer terms'],
                        'Find the value(s) of the variable that make it true',
                        'You evaluate an expression; you solve an equation. Blurring the two causes trouble later.'],
                    ['true_false', 'In the balance-scale model, an operation applied to one side must also be applied to the other.',
                        null, 'true',
                        'That is exactly what keeps the equation true — otherwise the balance breaks.'],
                ],
                'flashcards' => [
                    ['Variable', 'A letter standing in for a number that is unknown or can change.'],
                    ['Expression', 'Numbers, variables and operations with no equals sign, e.g. 3x + 5.'],
                    ['Equation', 'A statement that two expressions are equal, e.g. 3x + 5 = 20.'],
                    ['Evaluate vs solve', 'Evaluate an expression (number in, number out). Solve an equation (find the variable).'],
                ],
            ],

            // ── Mathematics: solving linear equations (G8) ─────────────
            'bAerID24QJ0' => [
                'quiz' => [
                    ['mcq', 'Solve: 4x - 7 = 21',
                        ['x = 3.5', 'x = 7', 'x = 14', 'x = 3'],
                        'x = 7',
                        'Add 7 to both sides to get 4x = 28, then divide by 4 to get x = 7.'],
                    ['mcq', 'What is the first step in solving 3x + 5 = 20?',
                        ['Divide both sides by 3', 'Subtract 5 from both sides', 'Multiply both sides by 3', 'Add 5 to both sides'],
                        'Subtract 5 from both sides',
                        'Undo operations in reverse order: remove the constant first, then the coefficient.'],
                    ['true_false', 'Substituting your answer back into the original equation is a reliable way to check it.',
                        null, 'true',
                        'It costs seconds and catches most arithmetic slips.'],
                ],
                'flashcards' => [
                    ['Linear equation', 'An equation where the variable appears only to the first power.'],
                    ['The goal when solving', 'Isolate the variable by undoing operations in reverse order.'],
                    ['Checking an answer', 'Substitute it back into the ORIGINAL equation and confirm both sides match.'],
                    ['Three common errors', 'Applying an operation to only one side; losing a negative sign; dividing only part of a side.'],
                ],
            ],

            // ── Mathematics: variables on both sides (G9) ──────────────
            'f15zA0PhSek' => [
                'quiz' => [
                    ['mcq', 'Solve: 5x + 3 = 2x + 18',
                        ['x = 3', 'x = 5', 'x = 7', 'x = 21'],
                        'x = 5',
                        'Subtract 2x to get 3x + 3 = 18, subtract 3 to get 3x = 15, divide by 3.'],
                    ['mcq', 'After simplifying, you are left with 7 = 7. What does that mean?',
                        ['No solution', 'x = 7', 'Every number is a solution', 'You made a mistake'],
                        'Every number is a solution',
                        'A statement that is always true means the equation holds for any value of the variable.'],
                    ['mcq', 'After simplifying, you are left with 7 = 4. What does that mean?',
                        ['x = 3', 'There is no solution', 'Every number is a solution', 'x = 0'],
                        'There is no solution',
                        'A statement that is never true means no value of the variable can satisfy the equation.'],
                ],
                'flashcards' => [
                    ['Strategy', 'Collect variable terms on one side, constants on the other, then solve as usual.'],
                    ['Which side to collect on', 'Move the smaller coefficient — it avoids negatives and reduces mistakes.'],
                    ['Identity', 'Variables cancel and you are left with something TRUE: every number is a solution.'],
                    ['No solution', 'Variables cancel and you are left with something FALSE: no value works.'],
                ],
            ],

            // ── Mathematics: completing the square (G11) ───────────────
            'KouDAzYl_bc' => [
                'quiz' => [
                    ['mcq', 'To complete the square on x² + 6x, what must you add?',
                        ['3', '6', '9', '36'],
                        '9',
                        'Take half the coefficient of x (which is 3) and square it, giving 9.'],
                    ['mcq', 'Solve x² + 6x + 5 = 0 by completing the square.',
                        ['x = -1 or x = -5', 'x = 1 or x = 5', 'x = -3 only', 'x = 2 or x = -8'],
                        'x = -1 or x = -5',
                        'x² + 6x = -5, add 9 to both sides: (x+3)² = 4, so x + 3 = ±2.'],
                    ['true_false', 'Completing the square works even when a quadratic cannot be factored neatly.',
                        null, 'true',
                        'That is its main advantage, and it is where the quadratic formula comes from.'],
                ],
                'flashcards' => [
                    ['The rule', 'x² + bx becomes a perfect square by adding (b/2)².'],
                    ['Keeping the equation true', 'Whatever you add to one side, add to the other (or subtract it again on the same side).'],
                    ['Why it matters', 'It always works, it derives the quadratic formula, and it converts to vertex form.'],
                    ['Vertex form', 'a(x - h)² + k, where (h, k) is the vertex — what completing the square produces.'],
                ],
            ],

            // ── Mathematics: solving by graphing (G12) ─────────────────
            '573yqfOoMwE' => [
                'quiz' => [
                    ['mcq', 'To solve f(x) = g(x) graphically, what do you look for?',
                        ['Where each graph crosses the x-axis', 'Where the two graphs intersect', 'The highest point of each graph', 'Where both graphs are increasing'],
                        'Where the two graphs intersect',
                        'At an intersection the two expressions have the same value, so that x is a solution.'],
                    ['mcq', 'The graphs never intersect. What does that tell you?',
                        ['There is exactly one solution', 'There are infinitely many solutions', 'There is no real solution', 'You graphed them wrongly'],
                        'There is no real solution',
                        'No intersection is a genuine answer, not a failure — the equation has no real solution.'],
                    ['true_false', 'Graphing generally gives exact solutions.',
                        null, 'false',
                        'It gives good approximations and tells you how many solutions exist. Use algebra when an exact value is required.'],
                ],
                'flashcards' => [
                    ['The core idea', 'Solutions of f(x) = g(x) are the x-values where the two graphs meet.'],
                    ['When graphing is most useful', 'When an equation cannot be solved neatly by algebra.'],
                    ['Reading the number of solutions', 'Two intersections = two solutions; one = one; none = no real solution.'],
                    ['The limitation', 'Approximate, not exact. Use it to check algebra, not replace it.'],
                ],
            ],

            // ── Mathematics: rational equations (G12) ──────────────────
            '3RdNPrNUi4s' => [
                'quiz' => [
                    ['mcq', 'What makes rational equations different from other equations?',
                        ['They have no solutions', 'A variable appears in a denominator', 'They are always quadratic', 'They cannot be graphed'],
                        'A variable appears in a denominator',
                        'That is what creates restricted values and the possibility of extraneous solutions.'],
                    ['mcq', 'Why can an extraneous solution appear?',
                        ['Because of rounding', 'Because multiplying by an expression containing the variable can introduce values the original forbids', 'Because the equation is quadratic', 'Because the denominator was ignored'],
                        'Because multiplying by an expression containing the variable can introduce values the original forbids',
                        'The cleared equation has no restriction; the original is undefined where a denominator is zero.'],
                    ['true_false', 'Checking your answers is optional when solving rational equations.',
                        null, 'false',
                        'It is part of the method — an unchecked extraneous solution is simply a wrong answer.'],
                ],
                'flashcards' => [
                    ['Method', 'Multiply every term by the least common denominator to clear fractions, then solve.'],
                    ['Extraneous solution', 'A value that satisfies the cleared equation but not the original — usually because it makes a denominator zero.'],
                    ['Before you solve', 'Note which values make any denominator zero. Those can never be answers.'],
                    ['After you solve', 'Discard any answer on that restricted list, then check the rest.'],
                ],
            ],
            // ── Physics: Newton's first law (G10) ──────────────────────
            '5-ZFOhHQS68' => [
                'quiz' => [
                    ['mcq', 'A puck slides across smooth ice and gradually slows. Why?',
                        ['Its inertia runs out', 'A small friction force acts on it', 'It loses mass', 'Nothing acts on it; motion naturally decays'],
                        'A small friction force acts on it',
                        'Motion does not decay on its own. Something unseen — friction, air resistance — must act to change it.'],
                    ['mcq', 'A book rests on a table. Which statement is correct?',
                        ['No forces act on it', 'Gravity acts but the table exerts no force', 'Gravity and the table\'s push cancel, giving zero net force', 'Only the table\'s push acts'],
                        'Gravity and the table\'s push cancel, giving zero net force',
                        'Forces can act without changing motion, provided they cancel.'],
                    ['true_false', 'A heavier object has more inertia than a lighter one.',
                        null, 'true',
                        'Mass is the measure of inertia — a loaded trolley is harder to start and to stop.'],
                ],
                'flashcards' => [
                    ["Newton's first law", 'An object stays at rest, or moves at constant velocity, unless a net force acts on it.'],
                    ['Inertia', 'The tendency of an object to resist a change in its motion. Mass measures it.'],
                    ['Net force', 'The overall force after all forces are combined. Zero net force means no change in motion.'],
                    ['Why everyday experience misleads', 'Friction and air resistance are invisible, so motion appears to decay by itself.'],
                ],
            ],

            // ── Physics: Newton's third law (G10) ──────────────────────
            'UD-nc50M-I0' => [
                'quiz' => [
                    ['mcq', 'A third-law force pair always acts on:',
                        ['The same object', 'Two different objects', 'Whichever object is heavier', 'Nothing — they cancel'],
                        'Two different objects',
                        'That is precisely why they never cancel each other out.'],
                    ['mcq', 'You push off a wall on a skateboard and move. Why does the wall not move?',
                        ['It pushes back harder', 'It is attached to a building of enormous mass', 'No force acts on the wall', 'Friction stops it'],
                        'It is attached to a building of enormous mass',
                        'Equal force, vastly greater mass, so negligible acceleration.'],
                    ['true_false', 'A rocket needs air to push against in order to accelerate.',
                        null, 'false',
                        'It pushes exhaust gas one way and the gas pushes it the other. No medium is required.'],
                ],
                'flashcards' => [
                    ["Newton's third law", 'If A exerts a force on B, B exerts an equal and opposite force on A.'],
                    ['Why the pair never cancels', 'The two forces act on different objects. Cancelling only happens within one object.'],
                    ['The test to apply', 'Name both objects for every force. Same object = not a third-law pair.'],
                    ['Rocket in vacuum', 'Pushes exhaust gas backwards; the gas pushes the rocket forwards. No air needed.'],
                ],
            ],

            // ── Physics: third law in practice (G12) ───────────────────
            'VfpKzwrhmqQ' => [
                'quiz' => [
                    ['mcq', 'A book on a table: gravity pulls down, the table pushes up. Are these a third-law pair?',
                        ['Yes, they are equal and opposite', 'No — they both act on the book', 'Yes, because nothing moves', 'Only if the book is stationary'],
                        'No — they both act on the book',
                        'Equal and opposite is not enough. A third-law pair must act on two different objects.'],
                    ['mcq', 'What is the third-law partner to the Earth pulling down on a book?',
                        ['The table pushing up on the book', 'The book pulling up on the Earth', 'Air pressure on the book', 'There is no partner'],
                        'The book pulling up on the Earth',
                        'The partner always involves the same two objects with the roles reversed.'],
                    ['mcq', 'In a tug of war, both teams pull on each other equally. What decides the winner?',
                        ['Which team pulls harder on the rope', 'Which team pushes harder against the ground', 'The length of the rope', 'Nobody can win'],
                        'Which team pushes harder against the ground',
                        'The rope forces are equal. The external force that moves the system comes from friction with the ground.'],
                ],
                'flashcards' => [
                    ['The identification test', 'For every force, name both objects. If two forces act on the SAME object they are not a third-law pair.'],
                    ['Walking', 'You push backwards on the ground; the ground pushes you forwards. No friction, no walking.'],
                    ['Horse and cart', 'The pair acts on different objects, so they do not cancel. The horse pushing the ground moves the system.'],
                    ['Common error', 'Calling gravity and the normal force a third-law pair. They act on the same object.'],
                ],
            ],

            // ── Science: Newton's first law, middle school (G6) ────────
            'Q0T2zjmvvA0' => [
                'quiz' => [
                    ['mcq', 'Why do you lurch forward when a bus brakes suddenly?',
                        ['The bus pushes you forward', 'Your body was moving and nothing stopped it yet', 'Gravity increases', 'The seat pushes you'],
                        'Your body was moving and nothing stopped it yet',
                        'This is inertia, and it is exactly why seatbelts exist.'],
                    ['true_false', 'A ball on a flat floor will start rolling by itself.',
                        null, 'false',
                        'Something must push it. At rest stays at rest until a force acts.'],
                    ['mcq', 'Which is harder to stop once moving?',
                        ['An empty shopping trolley', 'A full shopping trolley', 'Both the same', 'Depends on the colour'],
                        'A full shopping trolley',
                        'More mass means more inertia, so more force is needed to change its motion.'],
                ],
                'flashcards' => [
                    ['The first law, simply', 'Things keep doing what they are already doing unless something makes them change.'],
                    ['Inertia', 'Resistance to a change in motion. Heavier objects have more of it.'],
                    ['Why a rolling ball stops', 'Friction and air push against it — not because motion runs out.'],
                    ['Seatbelts', 'You keep moving when the vehicle stops. The belt supplies the force that stops you.'],
                ],
            ],

            // ── U.S. History: taxes and the road to revolution (G8) ────
            'Eytc9ZaNWyc' => [
                'quiz' => [
                    ['mcq', 'Why did Parliament start taxing the colonies more heavily in the 1760s?',
                        ['To fund a new navy', 'To pay down debt from the Seven Years\' War', 'To punish colonial smuggling', 'To pay for the Constitution'],
                        'To pay down debt from the Seven Years\' War',
                        'Britain finished the war deeply in debt and with more territory to defend.'],
                    ['mcq', 'What did "no taxation without representation" actually object to?',
                        ['The amount of the taxes', 'Parliament\'s right to tax colonies with no members there', 'Taxes on tea specifically', 'All forms of taxation'],
                        'Parliament\'s right to tax colonies with no members there',
                        'The dispute was constitutional — about who held the authority — more than about the sums.'],
                    ['true_false', 'Tighter enforcement of trade rules turned a commercial irritation into a rights grievance.',
                        null, 'true',
                        'Searches under general warrants made it a question of liberties, not just of money.'],
                ],
                'flashcards' => [
                    ['The financial background', 'Britain won the Seven Years\' War but finished heavily in debt, with more territory to defend.'],
                    ['The colonial objection', 'Not the amount, but the authority: no colonial members sat in Parliament.'],
                    ['Why smuggling mattered', 'Tighter enforcement, including general warrants, turned trade rules into a question of rights.'],
                    ['The escalation', 'Boycotts, the Boston Massacre, the destruction of tea, then Britain\'s punitive response.'],
                ],
            ],

            // ── U.S. History: who won the revolution (G8) ──────────────
            '3EiSymRrKI4' => [
                'quiz' => [
                    ['mcq', 'Which outside power was decisive to American victory?',
                        ['Spain', 'France', 'The Dutch Republic', 'Prussia'],
                        'France',
                        'French money, troops and naval power were central to the outcome.'],
                    ['mcq', 'What happened to slavery after the Revolution?',
                        ['It was abolished immediately', 'It survived and later expanded', 'It was confined to one state', 'It ended in the 1790s'],
                        'It survived and later expanded',
                        'Revolutionary language about liberty did not translate into emancipation.'],
                    ['mcq', 'Why did many Native nations lose from American independence?',
                        ['They fought only for the colonists', 'Independence removed British restraint on westward settlement', 'They were not involved', 'Their land was formally protected'],
                        'Independence removed British restraint on westward settlement',
                        'British policy had limited settlement westward; independence removed that check.'],
                ],
                'flashcards' => [
                    ['The military outcome', 'Independence secured, with decisive French support. Britain lost the war but not its empire.'],
                    ['Who gained', 'Propertied white men gained self-government and office.'],
                    ['Who did not', 'Enslaved people, Native nations, Loyalists, and women saw little or negative change.'],
                    ['The historian\'s question', 'Asking who benefited separates a change of rulers from a change in social power.'],
                ],
            ],

            // ── U.S. History: Constitution and federalism (G12) ────────
            'bO7FQsCcbD8' => [
                'quiz' => [
                    ['mcq', 'Which weakness of the Articles most directly caused financial failure?',
                        ['No national army', 'No power to tax directly', 'No bill of rights', 'A single-house Congress'],
                        'No power to tax directly',
                        'Congress depended on state requisitions the states frequently ignored.'],
                    ['mcq', 'What is federalism?',
                        ['Rule by a single central government', 'Power divided between national and state governments', 'Government by the courts', 'Direct democracy'],
                        'Power divided between national and state governments',
                        'The boundary between the two has been contested throughout American history.'],
                    ['true_false', 'The Bill of Rights was part of the Constitution as originally ratified.',
                        null, 'false',
                        'It was added by amendment, largely as the price of Anti-Federalist support.'],
                ],
                'flashcards' => [
                    ['Articles of Confederation', 'The first framework. Deliberately weak centre: no direct taxation, no executive, no federal judiciary.'],
                    ['Why it failed', 'Could not pay war debts, could not settle interstate trade disputes, struggled to maintain order.'],
                    ['The great compromise', 'A bicameral legislature balancing large and small states.'],
                    ['Federalists vs Anti-Federalists', 'Federalists wanted a stronger union; Anti-Federalists feared concentrated power and won the Bill of Rights.'],
                ],
            ],

            // ── U.S. History: Seven Years War (G6) ─────────────────────
            '5vKGU3aEGss' => [
                'quiz' => [
                    ['mcq', 'What did Britain gain from the Seven Years\' War?',
                        ['Large North American territory, and heavy debt', 'Independence', 'Control of Spain', 'Nothing at all'],
                        'Large North American territory, and heavy debt',
                        'The debt is what later drove attempts to tax the colonies.'],
                    ['mcq', 'What was one unintended effect of colonists fighting alongside British regulars?',
                        ['They became more loyal to Britain', 'They grew less impressed by them and worked across colonial lines', 'They stopped trading', 'They adopted British law'],
                        'They grew less impressed by them and worked across colonial lines',
                        'Both effects mattered when the colonies later organised together.'],
                    ['true_false', 'The Great Awakening encouraged people to question inherited authority.',
                        null, 'true',
                        'It emphasised personal conviction over established hierarchy, and crossed colonial boundaries.'],
                ],
                'flashcards' => [
                    ['Seven Years\' War outcome', 'Britain won and gained territory, but finished deeply in debt.'],
                    ['The Great Awakening', 'A wave of religious revival emphasising personal conviction over inherited authority.'],
                    ['Why they are taught together', 'Both encouraged colonists to question authority and to see themselves as connected.'],
                    ['An important caution', 'Neither caused the Revolution. They made a shared colonial identity possible.'],
                ],
            ],

            // ── Computer Science: algorithms (G11) ─────────────────────
            'rL8X2mlNHPM' => [
                'quiz' => [
                    ['mcq', 'Which is NOT a requirement for something to be an algorithm?',
                        ['It must terminate', 'Each step must be unambiguous', 'It must run on a computer', 'It must be correct for every valid input'],
                        'It must run on a computer',
                        'A recipe is an algorithm. The medium is irrelevant.'],
                    ['mcq', 'Searching a sorted list of one million items: roughly how many steps does binary search need?',
                        ['About one million', 'About one thousand', 'About twenty', 'About one hundred thousand'],
                        'About twenty',
                        'Each step halves the range, so the cost grows with the logarithm of the size.'],
                    ['true_false', 'Choosing a better algorithm usually beats buying a faster computer.',
                        null, 'true',
                        'A better growth rate outpaces hardware gains as input size increases.'],
                ],
                'flashcards' => [
                    ['Algorithm', 'A finite, unambiguous sequence of steps that solves a problem for every valid input.'],
                    ['Why we measure growth, not seconds', 'Seconds depend on the machine. Growth rate describes the algorithm itself.'],
                    ['Big-O notation', 'A description of how running time grows as the input grows.'],
                    ['Linear vs binary search', 'Linear: time proportional to n. Binary (sorted only): proportional to log n.'],
                ],
            ],

            // ── Computer Science: data structures (G11) ────────────────
            'DuDz6B4cqVc' => [
                'quiz' => [
                    ['mcq', 'Which structure gives immediate access to the nth item?',
                        ['Linked list', 'Array', 'Stack', 'Queue'],
                        'Array',
                        'Items sit in consecutive memory, so the position can be computed directly.'],
                    ['mcq', 'Undo history is naturally modelled by which structure?',
                        ['Queue', 'Stack', 'Array', 'Tree'],
                        'Stack',
                        'Last in, first out — the most recent action is the first undone.'],
                    ['mcq', 'What is the main cost of inserting into the middle of an array?',
                        ['It is impossible', 'Everything after it must shift', 'The array must be sorted first', 'It loses the index'],
                        'Everything after it must shift',
                        'That is exactly the case a linked list handles cheaply, once you are in position.'],
                ],
                'flashcards' => [
                    ['Array', 'Consecutive memory. Fast access by index; costly insertion in the middle.'],
                    ['Linked list', 'Each item points to the next. Cheap insertion in place; must walk from the start to reach an item.'],
                    ['Stack vs queue', 'Stack: last in, first out (undo). Queue: first in, first out (print jobs).'],
                    ['The central idea', 'There is no best structure — only the best fit for the operations you perform most.'],
                ],
            ],
        ];
    }

    public function alumni(): array
    {
        return [
            ['Nathaniel', 'Brooks', 'male', 2024],
            ['Priya', 'Raman', 'female', 2024],
            ['Dominic', 'Alvarez', 'male', 2025],
            ['Sarah', 'Whitfield', 'female', 2025],
            ['Joshua', 'Okonjo', 'male', 2025],
        ];
    }

    public function graduatingLevel(): int
    {
        return 12;
    }

    public function certificatePrefix(): string
    {
        return 'LHA';
    }

    public function pages(): array
    {
        return [
            [
                'title' => 'Home',
                'slug' => 'home',
                'content' => '<h2>Welcome to Lincoln Heights Academy</h2>'
                    . '<p>An independent K-12 day school in Austin, Texas, deliberately small enough that every student is known by name.</p>',
                'meta_title' => 'Lincoln Heights Academy — Austin, TX',
                'meta_description' => 'Lincoln Heights Academy is an independent K-12 college-preparatory day school in Austin, Texas, serving families since 1998.',
                'sort_order' => 1,
            ],
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => '<h2>About Lincoln Heights Academy</h2>'
                    . '<p>Founded in 1998, Lincoln Heights Academy has spent more than twenty-five years educating students from Kindergarten through Grade 12. We have grown carefully and stayed mid-sized on purpose.</p>'
                    . '<p>Sixteen faculty and staff serve roughly 100 students. Sections stay in the low teens, which is what makes our advisory system work: one teacher who genuinely tracks how each student is doing across every class.</p>',
                'meta_title' => 'About Lincoln Heights Academy',
                'meta_description' => 'Our history, mission, and the reasons we keep class sizes small.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Admissions',
                'slug' => 'admissions',
                'content' => '<h2>Admissions</h2>'
                    . '<p>We accept applications for Kindergarten through Grade 12 on a rolling basis, with priority review for families who apply before February 1.</p>'
                    . '<h3>What we need from you</h3>'
                    . '<ul>'
                    . '<li>Completed online application</li>'
                    . '<li>Transcript or report card release from your current school</li>'
                    . '<li>One teacher recommendation (Grades 5 and up)</li>'
                    . '<li>A student visit day on campus</li>'
                    . '</ul>'
                    . '<p>Need-based tuition assistance is reviewed every admissions cycle. Applying for aid does not affect an admission decision.</p>',
                'meta_title' => 'Admissions — Lincoln Heights Academy',
                'meta_description' => 'How to apply to Lincoln Heights Academy, what documents we need, and how tuition assistance works.',
                'sort_order' => 3,
            ],
            [
                'title' => 'Academics',
                'slug' => 'academics',
                'content' => '<h2>Academic Program</h2>'
                    . '<p>A college-preparatory core with real breadth. Honors and Advanced Placement pathways open in the upper grades, and college counseling begins in Grade 9 rather than senior year.</p>'
                    . '<h3>Lower School (K-5)</h3>'
                    . '<p>Language arts, mathematics, science, social studies, Spanish, art, and physical education.</p>'
                    . '<h3>Middle School (6-8)</h3>'
                    . '<p>English language arts, pre-algebra through geometry, life and physical science, U.S. history, Spanish, computer science, art, and athletics.</p>'
                    . '<h3>Upper School (9-12)</h3>'
                    . '<p>English, algebra II through calculus, biology, chemistry, physics, U.S. and world history, Spanish, computer science, studio art, and eleven interscholastic sports.</p>',
                'meta_title' => 'Academics — Lincoln Heights Academy',
                'meta_description' => 'Curriculum from Kindergarten through Grade 12, including Honors and AP pathways.',
                'sort_order' => 4,
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => '<h2>Get in Touch</h2>'
                    . '<p><strong>Address:</strong> 4820 Shoal Creek Blvd, Austin, TX 78756, United States</p>'
                    . '<p><strong>Phone:</strong> (512) 555-0100</p>'
                    . '<p><strong>Email:</strong> office@lincoln.kynexsolutions.com</p>'
                    . '<p>Front office: Monday to Friday, 7:45 AM to 4:00 PM</p>',
                'meta_title' => 'Contact Lincoln Heights Academy',
                'meta_description' => 'Reach Lincoln Heights Academy at (512) 555-0100 or office@lincoln.kynexsolutions.com.',
                'sort_order' => 5,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2>'
                    . '<p>This policy explains how Lincoln Heights Academy handles student and family data: we collect what the school needs to operate, keep it secure, and never sell or share it with third parties for marketing.</p>'
                    . '<p>Student education records are maintained in accordance with the Family Educational Rights and Privacy Act (FERPA). Parents and eligible students may request access to their records through the Registrar.</p>',
                'meta_title' => 'Privacy Policy',
                'meta_description' => 'How Lincoln Heights Academy handles student and family data, including FERPA rights.',
                'sort_order' => 6,
            ],
            [
                'title' => 'Terms of Use',
                'slug' => 'terms-of-use',
                'content' => '<h2>Terms of Use</h2>'
                    . '<p>This website and the family portal are provided for the use of enrolled families, staff, and prospective applicants. Portal accounts are personal and must not be shared.</p>',
                'meta_title' => 'Terms of Use',
                'meta_description' => 'Terms governing use of the Lincoln Heights Academy website and family portal.',
                'sort_order' => 7,
            ],
        ];
    }

    public function school(): array
    {
        return [
            'name' => 'Lincoln Heights Academy',
            'tagline' => 'Learn Boldly. Lead Kindly. — Austin, Texas',
            'address' => '4820 Shoal Creek Blvd, Austin, TX 78756, United States',
            'email' => 'office@lincoln.kynexsolutions.com',
            'phone' => '+1 (512) 555-0100',
            'whatsapp' => '+1 (512) 555-0101',
            'facebook' => 'https://facebook.com/lincolnheightsacademy',
            'twitter' => 'https://x.com/lincolnheightstx',
            'instagram' => 'https://instagram.com/lincolnheightsacademy',
            'youtube' => 'https://youtube.com/@lincolnheightsacademy',
            'city' => 'Austin',
            'website' => 'https://lincoln.kynexsolutions.com',
            'admission_form_url' => 'https://lincoln.kynexsolutions.com/admissions/apply',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'timezone' => 'America/Chicago',
        ];
    }

    /**
     * Term plans for every class and subject that has taught material.
     *
     * Keyed "Grade N|Subject". Each topic is one week of the plan; the seeder
     * counts weeks from the first day of the academic year, so the plan lines
     * up with the real calendar rather than with an abstract "week 1".
     *
     * 'match' attaches an existing lecture to the topic when its title
     * contains that phrase, which is what lets a student see where a video
     * sits in the course and a teacher see which weeks still have no material.
     *
     * @return array<string, array{title:string, description:string, topics:list<array{title:string, description:string, objective:string, match?:string}>}>
     */
    public function syllabusPlans(): array
    {
        return [
            'Grade 6|Science' => [
                'title' => 'Grade 6 Science — Motion, Matter and Earth Systems',
                'description' => 'A first full year of physical and earth science, built around hands-on investigation. Students learn to measure carefully, argue from evidence, and explain everyday motion and materials in scientific language.',
                'topics' => [
                    ['title' => 'Working like a scientist', 'description' => 'Observation, hypothesis, variables and fair tests. Students design and run a simple controlled experiment in the first week.', 'objective' => 'Identify the independent, dependent and controlled variables in an investigation.'],
                    ['title' => 'Measurement and the metric system', 'description' => 'Length, mass, volume and temperature. Precision, estimation and reading instruments without parallax error.', 'objective' => 'Take and record measurements to an appropriate precision with correct units.'],
                    ['title' => 'Forces and motion', 'description' => 'Push, pull, speed and direction. Introduces balanced and unbalanced forces using carts, ramps and everyday examples.', 'objective' => 'Predict how an object will move when the forces on it are balanced or unbalanced.', 'match' => "Newton's first law"],
                    ['title' => 'Newton and inertia in daily life', 'description' => 'Why passengers lurch forward when a bus stops, and why seatbelts exist. Inertia as resistance to a change in motion.', 'objective' => 'Explain a familiar event using the idea of inertia.'],
                    ['title' => 'States of matter', 'description' => 'Solids, liquids and gases as arrangements of particles. Melting, freezing, evaporation and condensation.', 'objective' => 'Use the particle model to explain a change of state.'],
                    ['title' => 'Mixtures and separation', 'description' => 'Solutions, suspensions, filtration, evaporation and magnetic separation, with a practical separation challenge.', 'objective' => 'Choose and justify a method for separating a given mixture.'],
                    ['title' => 'Earth in space', 'description' => 'Day and night, the seasons and the phases of the Moon, modelled with a lamp and globe.', 'objective' => 'Explain the seasons in terms of axial tilt rather than distance from the Sun.'],
                    ['title' => 'Weather and the water cycle', 'description' => 'Evaporation, condensation, precipitation and collection; reading a simple weather chart.', 'objective' => 'Trace a water molecule through a complete cycle and name each change of state.'],
                    ['title' => 'Rocks, soil and erosion', 'description' => 'How rock becomes soil, and how water and wind reshape land over time.', 'objective' => 'Describe one process of weathering and one of erosion, and distinguish them.'],
                    ['title' => 'Energy around us', 'description' => 'Kinetic, potential, thermal, light and sound energy, and transfers between them.', 'objective' => 'Trace the energy transfers in an everyday device.'],
                    ['title' => 'Ecosystems and interdependence', 'description' => 'Producers, consumers and decomposers; food chains and webs in a local habitat.', 'objective' => 'Predict the effect on a food web of removing one organism.'],
                    ['title' => 'Unit review and science fair project', 'description' => 'Students select a question from the year, design an investigation and present findings to the class.', 'objective' => 'Plan, run and report an investigation independently.'],
                ],
            ],

            'Grade 6|U.S. History' => [
                'title' => 'Grade 6 U.S. History — Colonial America to Independence',
                'description' => 'From the first permanent settlements to the break with Britain. The year is built around cause and effect, and around reading short primary sources without being intimidated by them.',
                'topics' => [
                    ['title' => 'Before colonisation', 'description' => 'The peoples already living in North America, their regions, and how geography shaped how they lived.', 'objective' => 'Describe how environment shaped the way of life of two Native nations.'],
                    ['title' => 'The first colonies', 'description' => 'Jamestown and Plymouth: why they were founded, why one nearly failed, and who paid for them.', 'objective' => 'Compare the motives behind two early colonies.'],
                    ['title' => 'Three colonial regions', 'description' => 'New England, Middle and Southern colonies — climate, crops, labour and religion.', 'objective' => 'Explain why the economies of the three regions diverged.'],
                    ['title' => 'Slavery in colonial America', 'description' => 'The transatlantic trade, its scale, and how it became embedded in the colonial economy. Taught with age-appropriate primary accounts.', 'objective' => 'Explain how slavery shaped the colonial economy and society.'],
                    ['title' => 'Life in the colonies', 'description' => 'Work, schooling, family and community for different groups, including who was excluded from each.', 'objective' => 'Contrast daily life for two different groups in the same colony.'],
                    ['title' => 'The Seven Years War and the Great Awakening', 'description' => 'The war that removed the French threat and left Britain in debt, alongside a religious revival that taught colonists to question authority.', 'objective' => 'Explain how a British victory made conflict with the colonies more likely.', 'match' => 'Seven Years'],
                    ['title' => 'Taxation and resistance', 'description' => 'Stamp Act, Townshend duties, boycotts and the argument over representation.', 'objective' => 'Summarise the colonial case against taxation by Parliament.'],
                    ['title' => 'From protest to war', 'description' => 'The Boston Massacre, the Tea Party, the Intolerable Acts and Lexington.', 'objective' => 'Place five events in order and explain how each raised the stakes.'],
                    ['title' => 'The Declaration of Independence', 'description' => 'Close reading of the preamble: what it claims, and who it left out.', 'objective' => 'Restate the Declaration\'s central argument in your own words.'],
                    ['title' => 'Fighting the war', 'description' => 'Why the Continental Army survived, the role of France, and the war away from the battlefield.', 'objective' => 'Give two reasons the colonies won despite being outmatched.'],
                    ['title' => 'A new nation', 'description' => 'The Articles of Confederation, why they were weak, and the push for a constitution.', 'objective' => 'Identify two failures of the Articles of Confederation.'],
                    ['title' => 'Review and document study', 'description' => 'Students build a short evidence-based argument from three primary sources.', 'objective' => 'Support a historical claim with evidence from a source.'],
                ],
            ],

            'Grade 7|Science' => [
                'title' => 'Grade 7 Science — Life Science and Ecosystems',
                'description' => 'Cells, energy and ecosystems, taught so that each level of organisation is connected to the next. Students should finish able to trace energy from sunlight to a predator.',
                'topics' => [
                    ['title' => 'Characteristics of living things', 'description' => 'What distinguishes living from non-living, and the levels of biological organisation.', 'objective' => 'List the characteristics common to all living organisms.'],
                    ['title' => 'Cells and their parts', 'description' => 'Plant and animal cells under the microscope; the job of each major organelle.', 'objective' => 'Identify the main organelles and state one function of each.'],
                    ['title' => 'Photosynthesis and energy flow', 'description' => 'How plants convert light energy into stored chemical energy, and why every food chain begins there.', 'objective' => 'Write the inputs and outputs of photosynthesis and explain where the energy goes.', 'match' => 'Photosynthesis and the flow of energy'],
                    ['title' => 'Respiration', 'description' => 'How organisms release the energy stored in sugar, and how respiration mirrors photosynthesis.', 'objective' => 'Compare photosynthesis and respiration as opposite processes.'],
                    ['title' => 'Food chains and food webs', 'description' => 'Trophic levels, energy loss between levels, and why top predators are few.', 'objective' => 'Explain why only about a tenth of energy passes to the next trophic level.'],
                    ['title' => 'Cycles of matter', 'description' => 'Carbon, water and nitrogen cycles, and the role of decomposers in each.', 'objective' => 'Trace carbon through at least four stages of its cycle.'],
                    ['title' => 'Populations and limiting factors', 'description' => 'Growth, carrying capacity, competition, predation and disease.', 'objective' => 'Predict how a population responds to a named limiting factor.'],
                    ['title' => 'Adaptation and natural selection', 'description' => 'Variation, selection pressure and change over generations, using clear worked examples.', 'objective' => 'Explain a named adaptation as the result of selection.'],
                    ['title' => 'Human impact on ecosystems', 'description' => 'Habitat loss, pollution and introduced species, with a local case study.', 'objective' => 'Evaluate one human activity and its ecological consequences.'],
                    ['title' => 'Body systems overview', 'description' => 'Digestive, circulatory and respiratory systems as a connected supply chain.', 'objective' => 'Describe how three systems cooperate to supply a working muscle.'],
                    ['title' => 'Heredity basics', 'description' => 'Traits, genes and inherited versus acquired characteristics.', 'objective' => 'Distinguish an inherited trait from a learned one.'],
                    ['title' => 'Ecosystem investigation', 'description' => 'Field sampling of a schoolyard plot, then written analysis of what was found.', 'objective' => 'Collect and interpret ecological data from a real site.'],
                ],
            ],

            'Grade 8|Mathematics' => [
                'title' => 'Grade 8 Mathematics — Algebra Foundations',
                'description' => 'The year that turns arithmetic into algebra. Every unit insists on the same habit: do the same thing to both sides, and check the answer in the original equation.',
                'topics' => [
                    ['title' => 'The number system', 'description' => 'Rational and irrational numbers, ordering, and approximating square roots.', 'objective' => 'Place rational and irrational numbers correctly on a number line.'],
                    ['title' => 'Exponents and scientific notation', 'description' => 'Laws of exponents, negative and zero powers, and calculating with very large and small numbers.', 'objective' => 'Apply the exponent laws to simplify an expression.'],
                    ['title' => 'Variables, expressions and equations', 'description' => 'What a variable stands for, how to build an expression from a situation, and what an equation actually asserts.', 'objective' => 'Translate a word problem into an equation and say what the variable represents.', 'match' => 'Variables, expressions'],
                    ['title' => 'Solving linear equations', 'description' => 'One and two step equations, then multi-step with brackets, solved by keeping the equation balanced.', 'objective' => 'Solve a multi-step linear equation and verify the solution.', 'match' => 'Solving linear equations'],
                    ['title' => 'Equations with variables on both sides', 'description' => 'Collecting like terms across the equals sign, and equations with no solution or infinitely many.', 'objective' => 'Solve equations with unknowns on both sides and recognise special cases.'],
                    ['title' => 'Inequalities', 'description' => 'Solving and graphing linear inequalities, including the sign flip when multiplying by a negative.', 'objective' => 'Solve a linear inequality and represent the solution on a number line.'],
                    ['title' => 'Proportional relationships', 'description' => 'Unit rate, constant of proportionality, and recognising proportionality in tables and graphs.', 'objective' => 'Identify the constant of proportionality from a table, graph or equation.'],
                    ['title' => 'Linear functions and slope', 'description' => 'Slope as rate of change, intercepts, and slope-intercept form.', 'objective' => 'Find the slope and intercept of a line from two points.'],
                    ['title' => 'Graphing linear equations', 'description' => 'Moving fluently between equation, table and graph.', 'objective' => 'Graph a line from its equation and read its equation from a graph.'],
                    ['title' => 'Systems of equations', 'description' => 'Solving by graphing and by substitution, and what the intersection means.', 'objective' => 'Solve a system of two linear equations and interpret the solution.'],
                    ['title' => 'The Pythagorean theorem', 'description' => 'Statement, proof by area, and applications including distance between two points.', 'objective' => 'Use the theorem to find a missing side and to compute a distance.'],
                    ['title' => 'Volume and review', 'description' => 'Cylinders, cones and spheres, followed by consolidation of the year\'s algebra.', 'objective' => 'Calculate the volume of a composite solid.'],
                ],
            ],

            'Grade 8|U.S. History' => [
                'title' => 'Grade 8 U.S. History — Revolution and the Republic',
                'description' => 'The Revolution and the founding, taught as a set of arguments rather than a list of dates. Students are pushed to ask who benefited from each decision.',
                'topics' => [
                    ['title' => 'The colonies on the eve of war', 'description' => 'Population, economy and the colonial relationship with Britain in 1763.', 'objective' => 'Describe the colonial economy on the eve of the imperial crisis.'],
                    ['title' => 'Taxes, smuggling and the road to revolution', 'description' => 'Why Britain taxed the colonies after 1763, how colonists evaded and resisted, and how a fiscal dispute became a constitutional one.', 'objective' => 'Explain how a dispute about revenue became a dispute about rights.', 'match' => 'Taxes, smuggling'],
                    ['title' => 'Protest becomes rebellion', 'description' => 'Committees of correspondence, the Continental Congress, and the first shots.', 'objective' => 'Trace the organisational steps from protest to armed rebellion.'],
                    ['title' => 'Declaring independence', 'description' => 'Common Sense, the drafting of the Declaration, and the decision to break irrevocably.', 'objective' => 'Analyse how the Declaration justifies rebellion.'],
                    ['title' => 'The war years', 'description' => 'Strategy, hardship at Valley Forge, foreign alliance and Yorktown.', 'objective' => 'Assess the relative importance of French support to the outcome.'],
                    ['title' => 'Who actually won the Revolution?', 'description' => 'Independence for whom: the position of enslaved people, women, Native nations and Loyalists after 1783.', 'objective' => 'Evaluate the Revolution\'s outcome from more than one perspective.', 'match' => 'Who actually won'],
                    ['title' => 'The Articles of Confederation', 'description' => 'A deliberately weak government, and the crises that exposed it.', 'objective' => 'Explain why the Articles could not raise revenue or keep order.'],
                    ['title' => 'The Constitutional Convention', 'description' => 'Compromise over representation, over slavery, and over the executive.', 'objective' => 'Describe two compromises and who each one favoured.'],
                    ['title' => 'Ratification and the Bill of Rights', 'description' => 'Federalists against Anti-Federalists, and the price of ratification.', 'objective' => 'Summarise the Anti-Federalist objection and how it was answered.'],
                    ['title' => 'The new government at work', 'description' => 'Washington\'s precedents, the first cabinet, and the emergence of parties.', 'objective' => 'Explain how political parties formed despite being unplanned.'],
                    ['title' => 'Expansion and its cost', 'description' => 'The Louisiana Purchase and the consequences for Native nations.', 'objective' => 'Assess the consequences of expansion for those already living there.'],
                    ['title' => 'Document-based assessment', 'description' => 'A full document-based question using sources from across the year.', 'objective' => 'Construct an evidence-based argument from multiple sources.'],
                ],
            ],
            'Grade 9|Biology' => [
                'title' => 'Biology I — Cells, Energy and Genetics',
                'description' => 'A first rigorous biology course. Energy capture and transfer runs through the whole year, so photosynthesis is taught early and returned to repeatedly.',
                'topics' => [
                    ['title' => 'The nature of biological inquiry', 'description' => 'Controls, replication, and why a single dramatic result proves little.', 'objective' => 'Critique an experimental design for missing controls.'],
                    ['title' => 'Biological molecules', 'description' => 'Carbohydrates, lipids, proteins and nucleic acids, and how structure determines function.', 'objective' => 'Relate the structure of a biological molecule to its role.'],
                    ['title' => 'Cell structure and transport', 'description' => 'Membranes, organelles, diffusion, osmosis and active transport.', 'objective' => 'Predict the direction of water movement across a membrane.'],
                    ['title' => 'Photosynthesis: building sugar from light', 'description' => 'The overall equation, where it happens, and why the products matter to every other organism.', 'objective' => 'Explain how light energy ends up stored in a glucose molecule.', 'match' => 'Photosynthesis: how plants'],
                    ['title' => 'Inside the light reactions', 'description' => 'Photosystems, the electron transport chain, ATP and NADPH, and the origin of the oxygen released.', 'objective' => 'Identify the source of the oxygen released during photosynthesis.', 'match' => 'light reactions'],
                    ['title' => 'The Calvin cycle', 'description' => 'Carbon fixation and the reduction of carbon dioxide to sugar, and why it is not called the dark reaction.', 'objective' => 'Describe what happens to a carbon atom entering the Calvin cycle.'],
                    ['title' => 'Cellular respiration', 'description' => 'Glycolysis, the Krebs cycle and oxidative phosphorylation, with an ATP tally.', 'objective' => 'Account for the ATP produced from one glucose molecule.'],
                    ['title' => 'Cell division', 'description' => 'Mitosis and the cell cycle, checkpoints, and what happens when control fails.', 'objective' => 'Describe each stage of mitosis and its purpose.'],
                    ['title' => 'Meiosis and variation', 'description' => 'Reduction division, crossing over, and the origin of genetic variety.', 'objective' => 'Explain two ways meiosis generates variation.'],
                    ['title' => 'Mendelian genetics', 'description' => 'Dominance, Punnett squares, and monohybrid and dihybrid crosses.', 'objective' => 'Predict offspring ratios from a genetic cross.'],
                    ['title' => 'DNA and protein synthesis', 'description' => 'Replication, transcription and translation as an information pathway.', 'objective' => 'Translate a short DNA sequence into an amino acid sequence.'],
                    ['title' => 'Evolution and evidence', 'description' => 'Natural selection with evidence from fossils, anatomy and molecular biology.', 'objective' => 'Cite three independent lines of evidence for common ancestry.'],
                ],
            ],

            'Grade 9|Mathematics' => [
                'title' => 'Algebra I',
                'description' => 'A full algebra course. The emphasis is on fluency with equations and on reading a graph as the same object as its equation.',
                'topics' => [
                    ['title' => 'Expressions and the properties of operations', 'description' => 'Simplifying, distributing and factoring out common terms.', 'objective' => 'Simplify an algebraic expression correctly and justify each step.'],
                    ['title' => 'Solving linear equations', 'description' => 'Multi-step equations, fractions and decimals, and checking solutions.', 'objective' => 'Solve a linear equation containing fractions.'],
                    ['title' => 'Equations with variables on both sides', 'description' => 'Gathering terms across the equals sign, and identifying equations with no solution or infinitely many.', 'objective' => 'Solve and classify equations with unknowns on both sides.', 'match' => 'variables on both sides'],
                    ['title' => 'Literal equations and formulas', 'description' => 'Rearranging a formula for a named variable.', 'objective' => 'Rearrange a formula to make a chosen variable the subject.'],
                    ['title' => 'Linear inequalities', 'description' => 'Solving, graphing and compound inequalities.', 'objective' => 'Solve a compound inequality and graph its solution set.'],
                    ['title' => 'Functions and notation', 'description' => 'Domain, range, function notation and whether a relation is a function.', 'objective' => 'Determine whether a relation is a function and state its domain.'],
                    ['title' => 'Linear functions and modelling', 'description' => 'Slope, intercepts, forms of a line, and fitting a line to real data.', 'objective' => 'Model a real situation with a linear function and interpret the slope.'],
                    ['title' => 'Systems of linear equations', 'description' => 'Graphing, substitution and elimination, with applied problems.', 'objective' => 'Choose an efficient method to solve a given system.'],
                    ['title' => 'Exponents and exponential growth', 'description' => 'Exponent laws and growth and decay models.', 'objective' => 'Distinguish linear from exponential growth in a table of values.'],
                    ['title' => 'Polynomials', 'description' => 'Adding, subtracting and multiplying polynomials; special products.', 'objective' => 'Multiply two binomials and simplify.'],
                    ['title' => 'Factoring', 'description' => 'Common factors, trinomials and the difference of squares.', 'objective' => 'Factor a quadratic trinomial with leading coefficient one.'],
                    ['title' => 'Introduction to quadratics', 'description' => 'Graphs of parabolas, roots, and solving by factoring.', 'objective' => 'Find the roots of a quadratic by factoring and check on the graph.'],
                ],
            ],

            'Grade 10|Physics' => [
                'title' => 'Physics — Mechanics and Energy',
                'description' => 'Classical mechanics from measurement to momentum. Students are expected to reason from free-body diagrams before reaching for a formula.',
                'topics' => [
                    ['title' => 'Measurement and uncertainty', 'description' => 'Units, significant figures and estimating uncertainty in a measurement.', 'objective' => 'Report a measurement with an appropriate uncertainty.'],
                    ['title' => 'Describing motion', 'description' => 'Displacement, velocity and acceleration, and reading motion graphs.', 'objective' => 'Interpret a velocity-time graph, including the meaning of its area.'],
                    ['title' => 'Equations of motion', 'description' => 'Uniform acceleration in one dimension, applied to falling bodies.', 'objective' => 'Solve a uniform acceleration problem using the kinematic equations.'],
                    ['title' => "Newton's first law: inertia", 'description' => 'Balanced forces, equilibrium, and why constant velocity needs no net force.', 'objective' => 'Explain why an object in equilibrium can still be moving.', 'match' => "first law: inertia"],
                    ['title' => "Newton's second law", 'description' => 'F = ma, free-body diagrams and problems with several forces.', 'objective' => 'Draw a free-body diagram and use it to find an acceleration.'],
                    ['title' => "Newton's third law: action and reaction", 'description' => 'Force pairs, why they do not cancel, and how walking, swimming and rockets work.', 'objective' => 'Identify both members of a force pair and the body each acts on.', 'match' => 'third law: action'],
                    ['title' => 'Friction and circular motion', 'description' => 'Static and kinetic friction, and centripetal force.', 'objective' => 'Identify the force providing the centripetal acceleration in a given case.'],
                    ['title' => 'Work, energy and power', 'description' => 'Work done by a force, kinetic and potential energy, and the work-energy theorem.', 'objective' => 'Solve a problem using conservation of mechanical energy.'],
                    ['title' => 'Momentum and collisions', 'description' => 'Impulse, conservation of momentum, and elastic versus inelastic collisions.', 'objective' => 'Apply conservation of momentum to a one-dimensional collision.'],
                    ['title' => 'Gravitation', 'description' => 'Universal gravitation, weight against mass, and orbits.', 'objective' => 'Distinguish mass from weight and calculate each.'],
                    ['title' => 'Simple machines and efficiency', 'description' => 'Mechanical advantage, and why no machine returns all the energy put in.', 'objective' => 'Calculate the efficiency of a simple machine.'],
                    ['title' => 'Practical assessment', 'description' => 'A measured investigation of acceleration on an inclined plane, written up in full.', 'objective' => 'Produce a laboratory report with analysed data and an uncertainty estimate.'],
                ],
            ],

            'Grade 11|Computer Science' => [
                'title' => 'Computer Science — Algorithms and Data Structures',
                'description' => 'Problem solving before syntax. Students learn to describe a method precisely, reason about its cost, and only then write it as code.',
                'topics' => [
                    ['title' => 'What is an algorithm?', 'description' => 'Precise steps, determinism and termination, using everyday procedures before any code.', 'objective' => 'Write an unambiguous algorithm for an everyday task.', 'match' => 'What is an algorithm'],
                    ['title' => 'Pseudocode and flowcharts', 'description' => 'Expressing an algorithm so that any programmer could implement it.', 'objective' => 'Express an algorithm in pseudocode a peer can follow.'],
                    ['title' => 'Variables, types and control flow', 'description' => 'Assignment, conditionals and loops, with tracing by hand.', 'objective' => 'Trace a program by hand and predict its output.'],
                    ['title' => 'Functions and decomposition', 'description' => 'Breaking a problem into parts, parameters, return values and scope.', 'objective' => 'Decompose a problem into functions with clear responsibilities.'],
                    ['title' => 'Arrays and lists', 'description' => 'Indexing, iteration and building the common list operations from scratch.', 'objective' => 'Implement a linear search and state its cost.'],
                    ['title' => 'Choosing how to store data', 'description' => 'Stacks, queues, dictionaries and sets, and matching a structure to the access pattern a problem needs.', 'objective' => 'Justify a data structure choice in terms of the operations required.', 'match' => 'Data structures'],
                    ['title' => 'Searching', 'description' => 'Linear and binary search, and the precondition binary search depends on.', 'objective' => 'Explain why binary search requires sorted data.'],
                    ['title' => 'Sorting', 'description' => 'Selection, insertion and merge sort, compared by counting operations.', 'objective' => 'Compare two sorting algorithms by their number of comparisons.'],
                    ['title' => 'Algorithmic complexity', 'description' => 'Big-O as a description of growth, and why constants matter less than shape.', 'objective' => 'Give the Big-O complexity of a short piece of code.'],
                    ['title' => 'Recursion', 'description' => 'Base cases, recursive cases and the call stack.', 'objective' => 'Write a recursive solution and identify its base case.'],
                    ['title' => 'Files and structured data', 'description' => 'Reading and writing files, and working with CSV and JSON.', 'objective' => 'Read structured data from a file and summarise it.'],
                    ['title' => 'Project: a working program', 'description' => 'Specification, implementation, test cases and a short written defence of the design.', 'objective' => 'Deliver a tested program and justify its structure.'],
                ],
            ],

            'Grade 11|Mathematics' => [
                'title' => 'Algebra II',
                'description' => 'Quadratics, polynomials and functions in depth. Completing the square is treated as the central technique it is, not a trick.',
                'topics' => [
                    ['title' => 'Review of linear systems', 'description' => 'Systems in two and three variables, solved by elimination and matrices.', 'objective' => 'Solve a three-variable system by elimination.'],
                    ['title' => 'Quadratic functions and their graphs', 'description' => 'Vertex, axis of symmetry, intercepts and transformations of the parabola.', 'objective' => 'Sketch a parabola from its equation, labelling key features.'],
                    ['title' => 'Solving quadratics by factoring', 'description' => 'The zero product property and the limits of factoring.', 'objective' => 'Solve a factorable quadratic and verify the roots.'],
                    ['title' => 'Completing the square', 'description' => 'Turning any quadratic into vertex form, and using it to solve equations and find the vertex directly.', 'objective' => 'Complete the square to find the vertex and solve the equation.', 'match' => 'Completing the square'],
                    ['title' => 'The quadratic formula and the discriminant', 'description' => 'Deriving the formula by completing the square, and reading the number of roots from the discriminant.', 'objective' => 'Use the discriminant to determine the nature of the roots.'],
                    ['title' => 'Complex numbers', 'description' => 'Imaginary units, arithmetic with complex numbers, and roots that are not real.', 'objective' => 'Add, multiply and simplify complex numbers.'],
                    ['title' => 'Polynomial functions', 'description' => 'Degree, end behaviour, and the relationship between roots and factors.', 'objective' => 'Predict the end behaviour of a polynomial from its leading term.'],
                    ['title' => 'Polynomial division and the remainder theorem', 'description' => 'Long and synthetic division, and factoring higher-degree polynomials.', 'objective' => 'Use synthetic division to test a candidate root.'],
                    ['title' => 'Rational expressions', 'description' => 'Simplifying, multiplying and adding rational expressions.', 'objective' => 'Add two rational expressions with unlike denominators.'],
                    ['title' => 'Radical functions and equations', 'description' => 'Rational exponents, solving radical equations and checking for extraneous roots.', 'objective' => 'Solve a radical equation and reject extraneous solutions.'],
                    ['title' => 'Exponential and logarithmic functions', 'description' => 'Inverses, logarithm laws and solving exponential equations.', 'objective' => 'Solve an exponential equation using logarithms.'],
                    ['title' => 'Sequences and series', 'description' => 'Arithmetic and geometric sequences and their sums.', 'objective' => 'Find the sum of a finite geometric series.'],
                ],
            ],
            'Grade 12|Biology' => [
                'title' => 'AP Biology — Energetics, Genetics and Systems',
                'description' => 'A college-level course. Students are expected to explain mechanisms, not name them, and to interpret data they have not seen before.',
                'topics' => [
                    ['title' => 'Chemistry of life', 'description' => 'Water, pH, macromolecules and the properties that make life chemically possible.', 'objective' => 'Relate a property of water to a biological consequence.'],
                    ['title' => 'Enzymes and energetics', 'description' => 'Activation energy, enzyme kinetics, inhibition and regulation.', 'objective' => 'Predict the effect of a named inhibitor on reaction rate.'],
                    ['title' => 'Membranes and transport', 'description' => 'Fluid mosaic structure, gradients and the cost of active transport.', 'objective' => 'Explain how a cell maintains a concentration gradient.'],
                    ['title' => 'Stages of photosynthesis', 'description' => 'Light reactions and the Calvin cycle traced in detail, including where each product goes.', 'objective' => 'Follow an electron from water to NADPH and say what happens at each step.', 'match' => 'stages of photosynthesis'],
                    ['title' => 'Photosynthetic variation', 'description' => 'C3, C4 and CAM strategies as responses to water loss.', 'objective' => 'Explain why C4 and CAM plants outperform C3 plants in hot climates.'],
                    ['title' => 'Cellular respiration in depth', 'description' => 'Chemiosmosis, the proton gradient and ATP yield.', 'objective' => 'Explain how a proton gradient is converted into ATP.'],
                    ['title' => 'Cell communication', 'description' => 'Signal transduction, receptors, second messengers and amplification.', 'objective' => 'Describe how one signal molecule produces a large cellular response.'],
                    ['title' => 'Cell cycle and its regulation', 'description' => 'Checkpoints, cyclins, apoptosis, and cancer as loss of control.', 'objective' => 'Explain how checkpoint failure can lead to uncontrolled division.'],
                    ['title' => 'Molecular genetics', 'description' => 'Replication, transcription, translation and the control of gene expression.', 'objective' => 'Predict the effect of a point mutation on the protein produced.'],
                    ['title' => 'Biotechnology', 'description' => 'PCR, gel electrophoresis, sequencing and gene editing, with their ethical questions.', 'objective' => 'Interpret a gel electrophoresis result.'],
                    ['title' => 'Evolution and population genetics', 'description' => 'Hardy-Weinberg equilibrium, selection, drift and speciation.', 'objective' => 'Use Hardy-Weinberg to test whether a population is evolving.'],
                    ['title' => 'Ecology and free-response practice', 'description' => 'Energy flow and population dynamics, with timed free-response writing.', 'objective' => 'Answer a data-based free-response question within the time limit.'],
                ],
            ],

            'Grade 12|Mathematics' => [
                'title' => 'Pre-Calculus',
                'description' => 'Functions in full generality, preparing directly for calculus. Every family of functions is examined algebraically and graphically together.',
                'topics' => [
                    ['title' => 'Functions and transformations', 'description' => 'Composition, inverses, shifts, stretches and reflections.', 'objective' => 'Describe the transformation taking one graph to another.'],
                    ['title' => 'Polynomial functions', 'description' => 'Roots, multiplicity, end behaviour and curve sketching.', 'objective' => 'Sketch a polynomial from its factored form.'],
                    ['title' => 'Rational functions', 'description' => 'Asymptotes, holes and the behaviour of a graph near a discontinuity.', 'objective' => 'Identify vertical, horizontal and slant asymptotes.'],
                    ['title' => 'Rational equations', 'description' => 'Solving equations containing algebraic fractions, and the extraneous solutions that arise when a denominator is zero.', 'objective' => 'Solve a rational equation and check for extraneous roots.', 'match' => 'Rational equations'],
                    ['title' => 'Solving equations by graphing', 'description' => 'Reading solutions as intersection points, and using graphs when an equation has no algebraic solution.', 'objective' => 'Solve an equation graphically and assess the accuracy of the reading.', 'match' => 'Solving equations by graphing'],
                    ['title' => 'Exponential and logarithmic functions', 'description' => 'Growth, decay, the natural base and logarithmic scales.', 'objective' => 'Model a growth situation and solve for the time to reach a target.'],
                    ['title' => 'Trigonometric functions', 'description' => 'The unit circle, radians, and the graphs of sine, cosine and tangent.', 'objective' => 'Evaluate a trigonometric function at any point on the unit circle.'],
                    ['title' => 'Trigonometric identities', 'description' => 'Pythagorean, sum and difference, and double angle identities.', 'objective' => 'Prove a trigonometric identity.'],
                    ['title' => 'Triangles and applications', 'description' => 'Law of sines and cosines, with bearing and surveying problems.', 'objective' => 'Solve a non-right triangle from given measurements.'],
                    ['title' => 'Vectors and parametric equations', 'description' => 'Components, magnitude, direction and motion described parametrically.', 'objective' => 'Resolve a vector into components and add two vectors.'],
                    ['title' => 'Sequences, series and induction', 'description' => 'Summation notation, convergence, and proof by induction.', 'objective' => 'Prove a summation formula by induction.'],
                    ['title' => 'Limits: a first look', 'description' => 'Limits numerically, graphically and algebraically, as the doorway to calculus.', 'objective' => 'Evaluate a limit algebraically, including an indeterminate form.'],
                ],
            ],

            'Grade 12|Physics' => [
                'title' => 'AP Physics — Mechanics',
                'description' => 'Calculus-ready mechanics. Students argue from principles and are marked on the reasoning, not only the number at the end.',
                'topics' => [
                    ['title' => 'Kinematics in one and two dimensions', 'description' => 'Vectors, projectile motion and relative velocity.', 'objective' => 'Solve a projectile problem by treating the components independently.'],
                    ['title' => "Newton's laws revisited", 'description' => 'Systems of connected bodies, tension and normal forces on inclines.', 'objective' => 'Solve for the acceleration of two connected masses.'],
                    ['title' => "Newton's third law in practice", 'description' => 'Force pairs in real systems — propulsion, recoil, and contact between accelerating bodies — and why the pair never cancels.', 'objective' => 'Analyse a multi-body system correctly using third-law pairs.', 'match' => 'third law in practice'],
                    ['title' => 'Friction and drag', 'description' => 'Coefficients of friction, terminal velocity and resistive forces.', 'objective' => 'Determine the terminal velocity of a falling body with drag.'],
                    ['title' => 'Circular motion and gravitation', 'description' => 'Centripetal force, orbital motion and Kepler\'s laws.', 'objective' => 'Derive orbital speed from the gravitational force.'],
                    ['title' => 'Work and energy', 'description' => 'Work by a variable force, conservative forces and potential energy curves.', 'objective' => 'Use a potential energy curve to describe the motion of a particle.'],
                    ['title' => 'Conservation of energy', 'description' => 'Mechanical energy, dissipation and problems combining several forms.', 'objective' => 'Solve a multi-stage problem using energy conservation.'],
                    ['title' => 'Momentum and impulse', 'description' => 'Impulse-momentum theorem, collisions in two dimensions and the centre of mass.', 'objective' => 'Solve a two-dimensional collision using momentum conservation.'],
                    ['title' => 'Rotational kinematics', 'description' => 'Angular displacement, velocity, acceleration and rolling without slipping.', 'objective' => 'Relate linear and angular quantities for a rolling body.'],
                    ['title' => 'Torque and rotational dynamics', 'description' => 'Moment of inertia, torque and the rotational form of the second law.', 'objective' => 'Calculate the angular acceleration produced by a torque.'],
                    ['title' => 'Angular momentum', 'description' => 'Conservation of angular momentum and its everyday demonstrations.', 'objective' => 'Apply conservation of angular momentum to a changing moment of inertia.'],
                    ['title' => 'Oscillations and exam practice', 'description' => 'Simple harmonic motion, pendulums, and timed past-paper work.', 'objective' => 'Derive the period of a simple harmonic oscillator.'],
                ],
            ],

            'Grade 12|U.S. History' => [
                'title' => 'AP U.S. Government and History — Founding and Federalism',
                'description' => 'The constitutional order: how it was designed, why it was designed that way, and how the arguments of 1787 still decide cases today.',
                'topics' => [
                    ['title' => 'Foundations of American democracy', 'description' => 'Natural rights, popular sovereignty, republicanism and the social contract.', 'objective' => 'Trace a constitutional principle back to its philosophical source.'],
                    ['title' => 'The Constitution, the Articles and federalism', 'description' => 'Why the Articles failed, what the Constitution changed, and how power was divided between nation and states.', 'objective' => 'Explain how the Constitution corrected specific failures of the Articles.', 'match' => 'Articles and federalism'],
                    ['title' => 'Separation of powers and checks and balances', 'description' => 'Federalist 51, ambition counteracting ambition, and the deliberate inefficiency of the design.', 'objective' => 'Explain the reasoning of Federalist 51 in your own words.'],
                    ['title' => 'The legislative branch', 'description' => 'Structure, powers, the committee system and why legislation usually fails.', 'objective' => 'Trace a bill through Congress and identify where most bills die.'],
                    ['title' => 'The executive branch', 'description' => 'Formal and informal powers, executive orders and the growth of the presidency.', 'objective' => 'Distinguish formal from informal presidential power with examples.'],
                    ['title' => 'The judiciary', 'description' => 'Judicial review from Marbury, precedent, and competing theories of interpretation.', 'objective' => 'Explain how Marbury established judicial review.'],
                    ['title' => 'Federalism in practice', 'description' => 'The commerce clause, the necessary and proper clause, grants and mandates.', 'objective' => 'Analyse a case where national and state authority conflicted.'],
                    ['title' => 'Civil liberties', 'description' => 'The Bill of Rights, incorporation, and the limits of speech and religion protections.', 'objective' => 'Apply a First Amendment precedent to a new fact pattern.'],
                    ['title' => 'Civil rights', 'description' => 'Equal protection, the long struggle for its enforcement, and landmark cases.', 'objective' => 'Explain how equal protection doctrine changed between Plessy and Brown.'],
                    ['title' => 'Political participation', 'description' => 'Elections, parties, interest groups and the media as linkage institutions.', 'objective' => 'Evaluate one institution\'s role in connecting citizens to government.'],
                    ['title' => 'Public policy and the budget', 'description' => 'How fiscal and social policy is made, and who has leverage at each stage.', 'objective' => 'Identify the veto points in the federal budget process.'],
                    ['title' => 'Required documents and exam practice', 'description' => 'The nine foundational documents and timed argumentative essay practice.', 'objective' => 'Write a timed argument citing a foundational document as evidence.'],
                ],
            ],
        ];
    }
}
