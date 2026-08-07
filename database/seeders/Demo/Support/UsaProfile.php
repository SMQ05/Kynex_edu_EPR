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
