<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\UsesDemoProfile;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * j. NotificationsSeeder
 *
 * notices (10-12 with target_roles JSON), in_app_notifications spread
 * across the user base.
 */
class NotificationsSeeder extends Seeder
{
    use UsesDemoProfile;

    public function __construct(
        public StaffSeeder $staff,
    ) {}

    public function run(): void
    {
        $this->seedNotices();
        $this->seedInAppNotifications();
    }

    protected function seedNotices(): void
    {
        DB::table('notices')->delete();

        $headId = $this->staff->userIdByLabel['principal']
            ?? $this->staff->userIdByLabel['admin'];
        $adminId = $this->staff->userIdByLabel['admin'];

        $items = [
            ['Annual Sports Day — 15th April 2026', 'Parents and students are warmly invited to the annual sports day at the main campus, from 9 AM. Refreshments will be served.', ['all'], $headId, now()->subDays(40)],
            ['Pakistan Day Holiday', 'School will remain closed on 23rd March in observance of Pakistan Day.', ['all'], $headId, now()->subDays(50)],
            ['First Term Exam Schedule Published', 'First term examinations begin 10th February. The full schedule has been shared on the academics page.', ['STUDENT', 'PARENT', 'TEACHER'], $headId, now()->subDays(80)],
            ['Mid Term Exam Schedule Published', 'Mid term examinations begin 15th April. Please check the academics page for class-wise schedule.', ['STUDENT', 'PARENT', 'TEACHER'], $headId, now()->subDays(28)],
            ['Fee Reminder — May 2026', 'Monthly tuition for May is due by the 10th. Late fee of PKR 200 applies after the due date.', ['PARENT'], $adminId, now()->subDays(2)],
            ['Parent-Teacher Meeting — 10th May', 'PTM is scheduled for Saturday 10th May from 9 AM to 1 PM. Please book your slot via the parent portal.', ['PARENT'], $headId, now()->subDays(7)],
            ['Class 10 Model Paper Distribution', 'Class 10 students will receive the board model papers on Monday during first period.', ['STUDENT'], $headId, now()->subDays(12)],
            ['Teacher Training Workshop — 18th April', 'All teaching staff are required to attend the professional development workshop on 18th April from 10 AM to 4 PM.', ['TEACHER'], $headId, now()->subDays(20)],
            ['Library Book Return Reminder', 'Please return all borrowed library books before 25th May for the annual stock check.', ['STUDENT', 'TEACHER'], $headId, now()->subDays(3)],
            ['Annual Day Rehearsals Begin', 'Annual day rehearsals begin Monday after school hours. All participating students must attend.', ['STUDENT'], $headId, now()->subDays(5)],
            ['New Cafeteria Menu', 'The school cafeteria has refreshed its menu with healthier options. Check the noticeboard for details.', ['STUDENT', 'PARENT', 'TEACHER'], $adminId, now()->subDays(10)],
        ];

        foreach ($items as [$title, $content, $targetRoles, $createdBy, $publishedAt]) {
            DB::table('notices')->insert([
                'id' => (string) Str::ulid(),
                'title' => $title,
                'content' => $content,
                'target_roles' => json_encode($targetRoles),
                'is_published' => true,
                'published_at' => $publishedAt,
                'expires_at' => $publishedAt->copy()->addDays(30),
                'created_by' => $createdBy,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);
        }
        $this->command?->line('  ✓ notices seeded (' . count($items) . ')');
    }

    protected function seedInAppNotifications(): void
    {
        DB::table('in_app_notifications')->delete();

        $sampleUsers = DB::table('school_users')
            ->where('is_active', true)
            ->whereIn('active_role', ['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'REGISTRAR', 'ACCOUNTANT', 'TEACHER', 'PARENT'])
            ->limit(40)
            ->pluck('id')
            ->all();

        $templates = [
            ['New Fee Receipt Generated', 'Receipt #{N} has been generated for student {S}.', 'success'],
            ['Attendance Marked', 'Today\'s attendance has been recorded.', 'info'],
            ['Result Card Available', 'Mid Term result card is now available for download.', 'info'],
            ['Fee Reminder', 'Monthly tuition is due in 3 days. Please pay by the 10th.', 'warning'],
            ['New Notice', 'A new school notice has been published. Tap to view.', 'info'],
            ['Homework Assigned', 'New homework assigned for class. Please review.', 'info'],
            ['Payment Received', 'We have received your fee payment. Thank you.', 'success'],
        ];

        $count = 0;
        $batch = [];
        foreach ($sampleUsers as $userId) {
            $perUser = mt_rand(1, 3);
            for ($i = 0; $i < $perUser; $i++) {
                $tpl = $templates[array_rand($templates)];
                $createdAt = now()->subDays(mt_rand(0, 30))->subHours(mt_rand(0, 23));
                $isRead = mt_rand(1, 100) <= 60;
                $batch[] = [
                    'id' => (string) Str::ulid(),
                    'user_id' => $userId,
                    'title' => $tpl[0],
                    'body' => str_replace(['{N}', '{S}'], [strtoupper(Str::random(6)), $this->profile()->certificatePrefix() . '-2025-' . str_pad((string) mt_rand(1, 100), 3, '0', STR_PAD_LEFT)], $tpl[1]),
                    'type' => $tpl[2],
                    'action_url' => null,
                    'read_at' => $isRead ? $createdAt->copy()->addHours(mt_rand(1, 12)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'push_sent_at' => null,
                    'push_delivered_at' => null,
                    'fallback_sent_at' => null,
                    'fallback_channel' => null,
                ];
                $count++;
                if (count($batch) >= 200) {
                    DB::table('in_app_notifications')->insert($batch);
                    $batch = [];
                }
            }
        }
        if (! empty($batch)) {
            DB::table('in_app_notifications')->insert($batch);
        }
        $this->command?->line("  ✓ in_app_notifications seeded ({$count})");
    }
}
