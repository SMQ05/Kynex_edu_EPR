<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\DemoProfile;

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

        $profile = DemoProfile::current();
        $school = $profile->school();
        $cms = $profile->cms();
        $json = static fn (array $v): string => (string) json_encode($v, JSON_UNESCAPED_UNICODE);

        DB::table('cms_settings')
            ->where('id', $row->id)
            ->update([
                'about_text' => $cms['about'],
                'vision_text' => $cms['vision'],
                'mission_text' => $cms['mission'],
                'principal_message' => $cms['principal_message'],
                'principal_name' => $profile->leadership()['principal']['name'],
                'why_choose_us' => $json($cms['why_choose_us']),
                'facilities' => $json($cms['facilities']),
                'testimonials' => $json($cms['testimonials']),
                'stats' => $json($cms['stats']),
                'exam_highlights' => $json($cms['exam_highlights']),
                'admission_steps' => $json($cms['admission_steps']),
                'hero_image_path' => $cms['hero_image'],
                'about_image_path' => $cms['about_image'],
                'admission_open' => true,
                'updated_at' => now(),
            ]);
        $this->command?->line('  ✓ cms_settings JSON columns filled (' . $school['name'] . ')');
    }

    protected function seedPages(): void
    {
        $profile = DemoProfile::current();
        $school = $profile->school();
        $cms = $profile->cms();

        DB::table('cms_pages')->delete();
        $pages = $profile->pages();

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
        $slides = DemoProfile::current()->sliders();
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
