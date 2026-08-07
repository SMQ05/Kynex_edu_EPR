<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * The original AQM Public School (Lahore) demo profile.
 *
 * Every locale method delegates straight to the untouched {@see Pak} helper
 * so the existing AQM demo seeds byte-for-byte identically to before the
 * profile refactor — same name pools, same mt_rand() call order, therefore
 * the same school after a reseed with the same mt_srand() seed.
 *
 * The school identity values were previously const on SchoolIdentitySeeder.
 */
final class PakProfile extends DemoProfile
{
    public function maleFirstNames(): array
    {
        return Pak::MALE_FIRST_NAMES;
    }

    public function femaleFirstNames(): array
    {
        return Pak::FEMALE_FIRST_NAMES;
    }

    public function surnames(): array
    {
        return Pak::SURNAMES;
    }

    public function cities(): array
    {
        return Pak::CITIES;
    }

    public function bloodGroupWeights(): array
    {
        return Pak::BLOOD_GROUP_WEIGHTS;
    }

    public function parentOccupations(): array
    {
        return Pak::PARENT_OCCUPATIONS;
    }

    public function paymentMethods(): array
    {
        return Pak::PAYMENT_METHODS;
    }

    public function phone(): string
    {
        return Pak::phone();
    }

    public function address(?string $cityName = null): array
    {
        return Pak::address($cityName);
    }

    /** Pakistani schools do record guardian CNIC, so supply one. */
    public function guardianDocumentNumber(): ?string
    {
        return Pak::cnic();
    }

    public function banks(): array
    {
        return ['HBL', 'UBL', 'MCB', 'Allied Bank', 'Meezan Bank', 'Bank Alfalah'];
    }

    /** Pakistani IBAN shape: PK + 2 check digits + 16 digits. */
    public function bankAccountNumber(): string
    {
        return 'PK'
            . str_pad((string) mt_rand(0, 99), 2, '0', STR_PAD_LEFT)
            . str_pad((string) mt_rand(1, 9_999_999), 16, '0', STR_PAD_LEFT);
    }

    public function emailDomain(): string
    {
        return 'aqmdigital.com';
    }

    public function leadership(): array
    {
        return [
            'admin' => [
                'name' => 'Qamar Abbas',
                'designation' => 'Office Manager',
                'qualification' => 'MBA (Administration)',
                'employee_id' => 'EMP-001',
                'salary' => 90_000_00,
            ],
            'principal' => [
                'name' => 'Khalid Mahmood',
                'designation' => 'Principal',
                'qualification' => 'MA Education, M.Phil',
                'employee_id' => 'EMP-002',
                'salary' => 150_000_00,
            ],
        ];
    }

    public function designations(): array
    {
        return [
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
    }

    public function salaryComponents(): array
    {
        return [
            ['Basic Salary', 'earning', 'fixed', 0, false],
            ['House Rent Allowance', 'allowance', 'percentage', 30, false],
            ['Conveyance Allowance', 'allowance', 'fixed', 300_000, false], // 3,000 PKR
            ['Medical Allowance', 'allowance', 'fixed', 200_000, false],
            ['Provident Fund', 'deduction', 'percentage', 5, false],
        ];
    }

    public function staffRoster(): array
    {
        return [
            // [authRole, designation, label, qualification, salaryPaisas, name, subject?]
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
    }

    public function cms(): array
    {
        return [
            'founded_year' => 2008,
            'grade_range' => 'Classes 1 through 10',
            'about_tagline' => "Lahore's trusted school since 2008",
            'office_hours' => 'School hours: Monday to Saturday, 7:30 AM to 2:00 PM',
            'about' => "AQM Public School was established in 2008 in the heart of Lahore with a vision to deliver excellent, holistic education to the youth of Pakistan. Over more than 17 years we have grown into a trusted institution serving over 100 students across Classes 1 through 10, supported by 18 dedicated faculty and staff members.\n\nOur curriculum balances rigorous academics with character-building, Islamic values, sports, and the arts. We believe every child deserves a learning environment where they are seen, supported and stretched.",
            'vision' => 'To be the most trusted school in Lahore — known for academic rigor, strong moral values, and graduates who lead with integrity in their communities.',
            'mission' => 'We educate the whole child: mind, character and body. Through dedicated teachers, modern facilities, and active parent partnership, we prepare every student to succeed in higher education and in life.',
            'principal_message' => "Welcome to AQM Public School. Our doors are open to families who share our commitment to excellence and integrity. As Principal, I am proud to lead a team of educators who treat every child as their own. Visit us, walk through our classrooms and labs, meet our teachers — and see what makes AQM different.",
            'facilities' => [
                ['name' => 'Science Laboratories', 'description' => 'Separate physics, chemistry and biology labs equipped for hands-on learning.'],
                ['name' => 'Computer Lab', 'description' => '24-station computer lab with high-speed internet and modern software.'],
                ['name' => 'Library', 'description' => 'Over 3,500 books across English, Urdu and academic subjects.'],
                ['name' => 'Sports Ground', 'description' => 'Cricket, football, and basketball facilities; annual sports day each spring.'],
                ['name' => 'Arts Studio', 'description' => 'Dedicated room for visual arts, calligraphy, and craft.'],
                ['name' => 'Transport', 'description' => 'Three school buses covering most major Lahore neighborhoods.'],
                ['name' => 'Quran Memorization Programme', 'description' => 'Optional Hifz programme alongside regular curriculum.'],
                ['name' => 'School Cafeteria', 'description' => 'Hygienic on-campus cafeteria offering balanced meals at subsidized rates.'],
            ],
            'testimonials' => [
                ['name' => 'Mr. Anwar Khan', 'relation' => 'Parent of Class 7 student', 'message' => 'AQM has shaped my son into a confident young man. The teachers genuinely care and the parent portal keeps us informed every day.'],
                ['name' => 'Mrs. Saima Iqbal', 'relation' => 'Parent of Class 3 student', 'message' => 'My daughter loves going to school. The atmosphere is warm but disciplined — exactly what we wanted.'],
                ['name' => 'Mr. Tariq Mahmood', 'relation' => 'Parent of Class 9 student', 'message' => 'Strong academics, fair fees, and an outstanding Principal. We have recommended AQM to many friends.'],
                ['name' => 'Ms. Bushra Saeed', 'relation' => 'Alumni parent', 'message' => 'Both of my older children graduated from AQM and are now in college. The foundation they got here was excellent.'],
            ],
            'stats' => [
                'students' => 100, 'teachers' => 12, 'established' => 2008,
                'pass_rate_percent' => 96, 'class_levels' => 10,
            ],
            'exam_highlights' => [
                ['exam' => 'First Term 2026', 'top_class' => 'Class 10', 'top_percent' => 94.5],
                ['exam' => 'Mid Term 2026', 'top_class' => 'Class 8', 'top_percent' => 93.2],
                ['exam' => 'Mid Term 2026', 'top_class' => 'Class 5', 'top_percent' => 92.8],
            ],
            'admission_steps' => [
                ['title' => 'Online Inquiry', 'description' => 'Submit the admission inquiry form online or visit the school office.'],
                ['title' => 'Entry Assessment', 'description' => 'Schedule a brief age-appropriate assessment with our academic team.'],
                ['title' => 'Document Submission', 'description' => 'Submit B-form copy, last school report card, and 2 passport photos.'],
                ['title' => 'Confirmation & Fee', 'description' => 'Pay the admission fee and first-month tuition to secure the seat.'],
            ],
            'hero_image' => 'https://placehold.co/1600x600/1a56db/ffffff?text=AQM+Public+School',
            'about_image' => 'https://placehold.co/800x600/1a56db/ffffff?text=AQM+About',
            'why_choose_us' => [
                ['title' => 'Qualified Faculty', 'icon' => 'academic-cap', 'description' => 'All teachers are graduates with at least a B.Ed and ongoing professional development.'],
                ['title' => 'Modern Facilities', 'icon' => 'building-office-2', 'description' => 'Well-equipped science and computer labs, library, and dedicated arts room.'],
                ['title' => 'Holistic Curriculum', 'icon' => 'sparkles', 'description' => 'Strong academics balanced with sports, Islamic studies, Quran, and arts.'],
                ['title' => 'Safe Campus', 'icon' => 'shield-check', 'description' => 'CCTV-monitored, gated campus with trained gatekeeping and bus tracking.'],
                ['title' => 'Active Parent Portal', 'icon' => 'user-group', 'description' => 'Real-time access to attendance, marks, fee status, and teacher messages.'],
                ['title' => 'Affordable Excellence', 'icon' => 'currency-rupee', 'description' => 'Competitive fees with sibling discounts and need-based scholarships.'],
            ],
        ];
    }

    public function gradeLevels(): array
    {
        $levels = [];
        for ($n = 1; $n <= 10; $n++) {
            $levels[$n] = "Class {$n}";
        }

        return $levels;
    }

    public function sectionsForLevel(int $level): array
    {
        return $level <= 5 ? ['A', 'B'] : ['A'];
    }

    public function classSubjects(): array
    {
        return [
            1 => ['Math', 'English', 'Urdu', 'Science', 'Islamiyat'],
            2 => ['Math', 'English', 'Urdu', 'Science', 'Islamiyat'],
            3 => ['Math', 'English', 'Urdu', 'Science', 'Islamiyat'],
            4 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
            5 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
            6 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
            7 => ['Math', 'English', 'Urdu', 'Science', 'Social Studies', 'Islamiyat', 'Computer'],
            8 => ['Math', 'English', 'Urdu', 'Physics', 'Chemistry', 'Biology', 'Computer', 'Islamiyat'],
            9 => ['Math', 'English', 'Urdu', 'Physics', 'Chemistry', 'Biology', 'Computer', 'Islamiyat'],
            10 => ['Math', 'English', 'Urdu', 'Physics', 'Chemistry', 'Biology', 'Computer', 'Islamiyat'],
        ];
    }

    public function subjectTeacherLabels(): array
    {
        return [
            'Math' => 'subject:Math',
            'English' => 'subject:English',
            'Urdu' => 'subject:Urdu',
            'Science' => 'subject:Science',
            'Physics' => 'subject:Science',          // bio-teacher proxies physics in primary demo
            'Chemistry' => 'subject:Science',
            'Biology' => 'subject:Science',
            'Social Studies' => 'subject:Social Studies',
            'Islamiyat' => 'subject:Islamiyat',
            'Quran' => 'subject:Quran',
            'Computer' => 'subject:Computer',
            'Arts' => 'subject:Arts',
            'Physical Education' => 'subject:Physical Education',
        ];
    }

    public function classSizes(): array
    {
        return [
            1 => 12, 2 => 11, 3 => 10, 4 => 10, 5 => 9,
            6 => 10, 7 => 10, 8 => 9, 9 => 10, 10 => 9,
        ];
    }

    public function subjects(): array
    {
        return [
            ['Math', 'MATH', '#ef4444'],
            ['English', 'ENG', '#3b82f6'],
            ['Urdu', 'URD', '#10b981'],
            ['Science', 'SCI', '#f59e0b'],
            ['Physics', 'PHY', '#8b5cf6'],
            ['Chemistry', 'CHEM', '#06b6d4'],
            ['Biology', 'BIO', '#22c55e'],
            ['Social Studies', 'SST', '#a855f7'],
            ['Islamiyat', 'ISL', '#14b8a6'],
            ['Computer', 'CS', '#0ea5e9'],
            ['Arts', 'ART', '#f43f5e'],
            ['Physical Education', 'PE', '#84cc16'],
            ['Quran', 'QRN', '#65a30d'],
        ];
    }

    public function sliders(): array
    {
        return [
            ['Excellence in Education', "Lahore's trusted school since 2008", 'Apply Now', '/apply'],
            ['Modern Facilities', 'Science labs, computer lab, library and sports ground', 'Take a Tour', '/about'],
            ['Active Parent Portal', 'Track attendance, marks and fees in real time', 'Learn More', '/about'],
            ['Admissions Open', 'Classes 1 through 10 — limited seats', 'Apply Today', '/apply'],
        ];
    }

    /** Pakistani standard A+ .. F. */
    public function gradeRules(): array
    {
        return [
            ['A+', 90, 100, 4.0, 'Outstanding'],
            ['A', 80, 89, 3.7, 'Excellent'],
            ['B+', 70, 79, 3.3, 'Very Good'],
            ['B', 60, 69, 3.0, 'Good'],
            ['C', 50, 59, 2.5, 'Satisfactory'],
            ['D', 40, 49, 2.0, 'Needs Improvement'],
            ['F', 0, 39, 0.0, 'Fail'],
        ];
    }

    public function recurringExpenses(): array
    {
        return [
                ['Utilities', 'Electricity bill', 80_000, 100_000],
                ['Utilities', 'Water bill', 8_000, 12_000],
                ['Utilities', 'Sui Gas bill', 6_000, 10_000],
                ['Internet & IT', 'Fiber internet (PTCL)', 12_000, 14_000],
                ['Rent', 'Building rent (Main Campus)', 250_000, 250_000],
            ];
    }

    public function periodicExpenses(): array
    {
        return [
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
    }

    public function certificatePrefix(): string
    {
        return 'AQM';
    }

    public function pages(): array
    {
        return [
                [
                    'title' => 'Home',
                    'slug' => 'home',
                    'content' => '<h2>Welcome to AQM Public School</h2><p>A trusted name in Lahore for over 17 years.</p>',
                    'meta_title' => 'AQM Public School — Lahore',
                    'meta_description' => 'AQM Public School is a leading institution in Lahore offering quality education from Class 1 through Class 10.',
                    'sort_order' => 1,
                ],
                [
                    'title' => 'About Us',
                    'slug' => 'about',
                    'content' => '<h2>About AQM Public School</h2><p>Founded in 2008, AQM Public School has spent more than seventeen years educating the children of Lahore. We balance academic rigor with character-building, Islamic values, and a love for learning.</p><p>Our 12 teachers and 6 support staff serve 100 students across Classes 1 through 10. We are proud of our 96% pass rate and the strong foundation our graduates take with them to higher education.</p>',
                    'meta_title' => 'About AQM Public School',
                    'meta_description' => 'Learn about AQM Public School — our history, mission and values.',
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Admissions',
                    'slug' => 'admissions',
                    'content' => '<h2>Admissions Open</h2><p>We welcome applications for Classes 1 through 10. Please visit the school office or submit the online inquiry form. The admission process is simple and family-friendly.</p><h3>Requirements</h3><ul><li>Copy of student B-form</li><li>Last school report card / leaving certificate</li><li>Two passport-size photos</li><li>Parent CNIC copy</li></ul>',
                    'meta_title' => 'Admissions — AQM Public School',
                    'meta_description' => 'Apply for admission to AQM Public School. Open for Classes 1 to 10.',
                    'sort_order' => 3,
                ],
                [
                    'title' => 'Academics',
                    'slug' => 'academics',
                    'content' => '<h2>Academic Programme</h2><p>Our curriculum follows the Punjab Curriculum framework with additional emphasis on Quran, English communication, and computer literacy.</p><h3>Class 1-3</h3><p>Math, English, Urdu, Science, Islamiyat.</p><h3>Class 4-7</h3><p>Math, English, Urdu, Science, Social Studies, Islamiyat, Computer.</p><h3>Class 8-10</h3><p>Math, English, Urdu, Physics, Chemistry, Biology, Computer, Islamiyat.</p>',
                    'meta_title' => 'Academics — AQM Public School',
                    'meta_description' => 'Subjects taught at AQM Public School from Class 1 to Class 10.',
                    'sort_order' => 4,
                ],
                [
                    'title' => 'Contact Us',
                    'slug' => 'contact',
                    'content' => '<h2>Get in Touch</h2>'
                        . '<p><strong>Address:</strong> ' . 'Plot 142, Block C, Johar Town, Lahore, Punjab 54600, Pakistan' . '</p>'
                        . '<p><strong>Phone:</strong> ' . '+92-42-1234-5678' . '</p>'
                        . '<p><strong>Email:</strong> ' . 'info@aqmdigital.com' . '</p>'
                        . '<p>' . 'School hours: Monday to Saturday, 7:30 AM to 2:00 PM' . '</p>',
                    'meta_title' => 'Contact AQM Public School',
                    'meta_description' => 'Reach ' . 'AQM Public School' . ' at ' . '+92-42-1234-5678' . ' or ' . 'info@aqmdigital.com' . '.',
                    'sort_order' => 5,
                ],
                [
                    'title' => 'Privacy Policy',
                    'slug' => 'privacy-policy',
                    'content' => '<h2>Privacy Policy</h2><p>This policy explains how AQM Public School handles parent and student data — minimally, securely, and never shared with third parties for marketing.</p>',
                    'meta_title' => 'Privacy Policy',
                    'meta_description' => 'AQM Public School privacy policy.',
                    'sort_order' => 6,
                ],
                [
                    'title' => 'Terms of Use',
                    'slug' => 'terms-of-use',
                    'content' => '<h2>Terms of Use</h2><p>By using this website, parents agree to the school\'s code of conduct and acceptable-use policies.</p>',
                    'meta_title' => 'Terms of Use',
                    'meta_description' => 'Terms of use for the AQM Public School website.',
                    'sort_order' => 7,
                ],
            ];
    }

    public function school(): array
    {
        return [
            'name' => 'AQM Public School',
            'tagline' => 'Excellence in Education — Lahore, Pakistan',
            'address' => 'Plot 142, Block C, Johar Town, Lahore, Punjab 54600, Pakistan',
            'email' => 'info@aqmdigital.com',
            'phone' => '+92-42-1234-5678',
            'city' => 'Lahore',
            'website' => 'https://aqmdigital.com',
            'admission_form_url' => 'https://aqmdigital.com/apply',
            'currency_code' => 'PKR',
            'currency_symbol' => 'Rs',
            'timezone' => 'Asia/Karachi',
        ];
    }
}
