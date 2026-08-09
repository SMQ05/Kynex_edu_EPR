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
}
