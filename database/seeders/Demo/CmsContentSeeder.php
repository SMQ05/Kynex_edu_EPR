<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * i. CmsContentSeeder
 *
 * Public website content: cms_pages (Home/About/Admissions/Academics/
 * Contact/Privacy/Terms), cms_sliders (4 hero slides), cms_gallery_albums
 * (3 albums × 4 photos), cms_announcements (6 website-facing notices).
 *
 * Also fills in the JSON columns on cms_settings (why_choose_us,
 * facilities, testimonials, stats, exam_highlights, admission_steps)
 * so the public site has something to render.
 */
class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->fillSettingsJson();
        $this->seedPages();
        $this->seedSliders();
        $this->seedGallery();
        $this->seedAnnouncements();
    }

    protected function fillSettingsJson(): void
    {
        $row = DB::table('cms_settings')->orderBy('created_at')->first();
        if (! $row) {
            return;
        }

        DB::table('cms_settings')
            ->where('id', $row->id)
            ->update([
                'about_text' => "AQM Public School was established in 2008 in the heart of Lahore with a vision to deliver excellent, holistic education to the youth of Pakistan. Over more than 17 years we have grown into a trusted institution serving over 100 students across Classes 1 through 10, supported by 18 dedicated faculty and staff members.\n\nOur curriculum balances rigorous academics with character-building, Islamic values, sports, and the arts. We believe every child deserves a learning environment where they are seen, supported and stretched.",
                'vision_text' => 'To be the most trusted school in Lahore — known for academic rigor, strong moral values, and graduates who lead with integrity in their communities.',
                'mission_text' => 'We educate the whole child: mind, character and body. Through dedicated teachers, modern facilities, and active parent partnership, we prepare every student to succeed in higher education and in life.',
                'principal_message' => "Welcome to AQM Public School. Our doors are open to families who share our commitment to excellence and integrity. As Principal, I am proud to lead a team of educators who treat every child as their own. Visit us, walk through our classrooms and labs, meet our teachers — and see what makes AQM different.",
                'principal_name' => 'Khalid Mahmood',
                'why_choose_us' => json_encode([
                    ['title' => 'Qualified Faculty', 'icon' => 'academic-cap', 'description' => 'All teachers are graduates with at least a B.Ed and ongoing professional development.'],
                    ['title' => 'Modern Facilities', 'icon' => 'building-office-2', 'description' => 'Well-equipped science and computer labs, library, and dedicated arts room.'],
                    ['title' => 'Holistic Curriculum', 'icon' => 'sparkles', 'description' => 'Strong academics balanced with sports, Islamic studies, Quran, and arts.'],
                    ['title' => 'Safe Campus', 'icon' => 'shield-check', 'description' => 'CCTV-monitored, gated campus with trained gatekeeping and bus tracking.'],
                    ['title' => 'Active Parent Portal', 'icon' => 'user-group', 'description' => 'Real-time access to attendance, marks, fee status, and teacher messages.'],
                    ['title' => 'Affordable Excellence', 'icon' => 'currency-rupee', 'description' => 'Competitive fees with sibling discounts and need-based scholarships.'],
                ], JSON_UNESCAPED_UNICODE),
                'facilities' => json_encode([
                    ['name' => 'Science Laboratories', 'description' => 'Separate physics, chemistry and biology labs equipped for hands-on learning.'],
                    ['name' => 'Computer Lab', 'description' => '24-station computer lab with high-speed internet and modern software.'],
                    ['name' => 'Library', 'description' => 'Over 3,500 books across English, Urdu and academic subjects.'],
                    ['name' => 'Sports Ground', 'description' => 'Cricket, football, and basketball facilities; annual sports day each spring.'],
                    ['name' => 'Arts Studio', 'description' => 'Dedicated room for visual arts, calligraphy, and craft.'],
                    ['name' => 'Transport', 'description' => 'Three school buses covering most major Lahore neighborhoods.'],
                    ['name' => 'Quran Memorization Programme', 'description' => 'Optional Hifz programme alongside regular curriculum.'],
                    ['name' => 'School Cafeteria', 'description' => 'Hygienic on-campus cafeteria offering balanced meals at subsidized rates.'],
                ], JSON_UNESCAPED_UNICODE),
                'testimonials' => json_encode([
                    ['name' => 'Mr. Anwar Khan', 'relation' => 'Parent of Class 7 student', 'message' => 'AQM has shaped my son into a confident young man. The teachers genuinely care and the parent portal keeps us informed every day.'],
                    ['name' => 'Mrs. Saima Iqbal', 'relation' => 'Parent of Class 3 student', 'message' => 'My daughter loves going to school. The atmosphere is warm but disciplined — exactly what we wanted.'],
                    ['name' => 'Mr. Tariq Mahmood', 'relation' => 'Parent of Class 9 student', 'message' => 'Strong academics, fair fees, and an outstanding Principal. We have recommended AQM to many friends.'],
                    ['name' => 'Ms. Bushra Saeed', 'relation' => 'Alumni parent', 'message' => 'Both of my older children graduated from AQM and are now in college. The foundation they got here was excellent.'],
                ], JSON_UNESCAPED_UNICODE),
                'stats' => json_encode([
                    'students' => 100,
                    'teachers' => 12,
                    'established' => 2008,
                    'pass_rate_percent' => 96,
                    'class_levels' => 10,
                ], JSON_UNESCAPED_UNICODE),
                'exam_highlights' => json_encode([
                    ['exam' => 'First Term 2026', 'top_class' => 'Class 10', 'top_percent' => 94.5],
                    ['exam' => 'Mid Term 2026', 'top_class' => 'Class 8', 'top_percent' => 93.2],
                    ['exam' => 'Mid Term 2026', 'top_class' => 'Class 5', 'top_percent' => 92.8],
                ], JSON_UNESCAPED_UNICODE),
                'admission_steps' => json_encode([
                    ['title' => 'Online Inquiry', 'description' => 'Submit the admission inquiry form online or visit the school office.'],
                    ['title' => 'Entry Assessment', 'description' => 'Schedule a brief age-appropriate assessment with our academic team.'],
                    ['title' => 'Document Submission', 'description' => 'Submit B-form copy, last school report card, and 2 passport photos.'],
                    ['title' => 'Confirmation & Fee', 'description' => 'Pay the admission fee and first-month tuition to secure the seat.'],
                ], JSON_UNESCAPED_UNICODE),
                'hero_image_path' => 'https://placehold.co/1600x600/1a56db/ffffff?text=AQM+Public+School',
                'about_image_path' => 'https://placehold.co/800x600/1a56db/ffffff?text=AQM+About',
                'admission_open' => true,
                'updated_at' => now(),
            ]);
        $this->command?->line('  ✓ cms_settings JSON columns filled');
    }

    protected function seedPages(): void
    {
        DB::table('cms_pages')->delete();
        $pages = [
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
                'content' => '<h2>Get in Touch</h2><p><strong>Address:</strong> Plot 142, Block C, Johar Town, Lahore</p><p><strong>Phone:</strong> +92-42-1234-5678</p><p><strong>Email:</strong> info@aqmdigital.com</p><p>School hours: Monday to Saturday, 7:30 AM to 2:00 PM</p>',
                'meta_title' => 'Contact AQM Public School',
                'meta_description' => 'Reach AQM Public School at +92-42-1234-5678 or info@aqmdigital.com.',
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

        foreach ($pages as $p) {
            DB::table('cms_pages')->insert([
                'id' => (string) Str::ulid(),
                'title' => $p['title'],
                'slug' => $p['slug'],
                'content' => $p['content'],
                'meta_title' => $p['meta_title'],
                'meta_description' => $p['meta_description'],
                'is_published' => true,
                'published_at' => now()->subDays(20),
                'sort_order' => $p['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ cms_pages seeded (' . count($pages) . ')');
    }

    protected function seedSliders(): void
    {
        DB::table('cms_sliders')->delete();
        $slides = [
            ['Excellence in Education', 'Lahore\'s trusted school since 2008', 'Apply Now', '/apply'],
            ['Modern Facilities', 'Science labs, computer lab, library and sports ground', 'Take a Tour', '/about'],
            ['Active Parent Portal', 'Track attendance, marks and fees in real time', 'Learn More', '/about'],
            ['Admissions Open', 'Classes 1 through 10 — limited seats', 'Apply Today', '/apply'],
        ];
        foreach ($slides as $i => [$title, $subtitle, $btn, $url]) {
            DB::table('cms_sliders')->insert([
                'id' => (string) Str::ulid(),
                'title' => $title,
                'subtitle' => $subtitle,
                'image_path' => "https://placehold.co/1600x600/1a56db/ffffff?text=" . urlencode($title),
                'button_text' => $btn,
                'button_url' => $url,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command?->line('  ✓ cms_sliders seeded (' . count($slides) . ')');
    }

    protected function seedGallery(): void
    {
        DB::table('cms_gallery_photos')->delete();
        DB::table('cms_gallery_albums')->delete();
        $albums = [
            ['Annual Sports Day 2025', 'Cricket, football and athletics — March 2025', '#22c55e'],
            ['Science Fair 2025', 'Class 6-10 student projects on display', '#3b82f6'],
            ['Independence Day Celebration', '14th August 2025 assembly and march', '#ef4444'],
        ];
        foreach ($albums as $i => [$title, $desc, $color]) {
            $albumId = (string) Str::ulid();
            DB::table('cms_gallery_albums')->insert([
                'id' => $albumId,
                'title' => $title,
                'description' => $desc,
                'cover_image_path' => "https://placehold.co/800x600/" . substr($color, 1) . "/ffffff?text=" . urlencode($title),
                'sort_order' => $i + 1,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            for ($p = 1; $p <= 4; $p++) {
                DB::table('cms_gallery_photos')->insert([
                    'id' => (string) Str::ulid(),
                    'album_id' => $albumId,
                    'title' => "{$title} — Photo {$p}",
                    'image_path' => "https://placehold.co/800x600/" . substr($color, 1) . "/ffffff?text=" . urlencode($title . ' ' . $p),
                    'sort_order' => $p,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command?->line('  ✓ cms_gallery_albums (3) and photos (12) seeded');
    }

    protected function seedAnnouncements(): void
    {
        DB::table('cms_announcements')->delete();
        $items = [
            ['Admissions Open for 2026-2027', 'Applications are now being accepted for the next academic year.', now()->subDays(15), now()->addDays(60)],
            ['Annual Sports Day — 15th April 2026', 'Parents are invited to attend the annual sports day at the main campus.', now()->subDays(30), now()->subDays(20)],
            ['First Term Result Cards Available', 'First term result cards can be downloaded from the parent portal.', now()->subDays(45), now()->subDays(15)],
            ['Mid Term Exam Schedule Published', 'Schedule for the mid term examinations is available on the academics page.', now()->subDays(25), now()->subDays(10)],
            ['Parent-Teacher Meeting — 10th May', 'PTM scheduled for Saturday 10th May 2026 from 9 AM to 1 PM.', now()->subDays(8), now()->addDays(7)],
            ['School Closed on Pakistan Day', 'School will remain closed on 23rd March in observance of Pakistan Day.', now()->subDays(50), now()->subDays(40)],
        ];
        foreach ($items as [$title, $content, $publishedAt, $expiresAt]) {
            DB::table('cms_announcements')->insert([
                'id' => (string) Str::ulid(),
                'title' => $title,
                'content' => $content,
                'is_published' => true,
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);
        }
        $this->command?->line('  ✓ cms_announcements seeded (' . count($items) . ')');
    }
}
