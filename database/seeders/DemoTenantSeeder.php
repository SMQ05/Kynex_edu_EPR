<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Demo\AttendanceSeeder;
use Database\Seeders\Demo\ClassesSeeder;
use Database\Seeders\Demo\CmsContentSeeder;
use Database\Seeders\Demo\ExamsAndResultsSeeder;
use Database\Seeders\Demo\FeesSeeder;
use Database\Seeders\Demo\FinanceSeeder;
use Database\Seeders\Demo\IdCardsAndCertificatesSeeder;
use Database\Seeders\Demo\LecturesAndAssignmentsSeeder;
use Database\Seeders\Demo\OnlineExamsSeeder;
use Database\Seeders\Demo\NotificationsSeeder;
use Database\Seeders\Demo\SchoolIdentitySeeder;
use Database\Seeders\Demo\StaffSeeder;
use Database\Seeders\Demo\StudentsAndParentsSeeder;
use Database\Seeders\Demo\SyllabusSeeder;
use Database\Seeders\Demo\TimetableSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo seeder for tenant haji-qamar-public-school-BEb3S9 (aqmdigital.com).
 *
 *  Run order (operator-approved):
 *    a. SchoolIdentitySeeder      — rename + casing fix + campus consolidation
 *    b. StaffSeeder               — 16 new staff + update 2 preserved (admin, head)
 *    c. ClassesSeeder             — academic year, classes 1-10, sections, subjects, class_subjects
 *    d. StudentsAndParentsSeeder  — 100 active students + 5 alumni, ~75 parent identities
 *    e. FeesSeeder
 *    f. AttendanceSeeder
 *    g. ExamsAndResultsSeeder
 *    h. FinanceSeeder
 *    i. CmsContentSeeder
 *    j. NotificationsSeeder
 *    k. IdCardsAndCertificatesSeeder
 *
 *  Run modes:
 *    Without --fresh: aborts if students > 0 (refuses to overwrite).
 *    With --fresh: prints wipe summary, prompts unless DEMO_FORCE=1, then
 *      wipes operational tables (preserving admin + head, RBAC rows,
 *      system templates) and reseeds.
 *
 *  --fresh detection:
 *    - DEMO_FRESH=1 environment variable, OR
 *    - the literal token '--fresh' anywhere in the process argv (works
 *      around tenants:run argument-quoting awkwardness).
 *
 *  --force detection (skip the y/N prompt):
 *    - DEMO_FORCE=1 environment variable, OR
 *    - '--force' in argv.
 *
 *  Usage:
 *    docker exec -e DEMO_FRESH=1 kynexedu-app \
 *      php artisan tenants:run db:seed \
 *      --argument='class=DemoTenantSeeder' \
 *      --tenants=haji-qamar-public-school-BEb3S9
 */
class DemoTenantSeeder extends Seeder
{
    public const TARGET_TENANT_ID = 'haji-qamar-public-school-BEb3S9';

    /**
     * Wipe set — operational tables that --fresh empties before reseed.
     * Order is leaves-first so FK CASCADE isn't strictly required, but
     * we still pass through DELETE not TRUNCATE so we can keep specific
     * school_users rows (admin, head).
     *
     * IDs of preserved rows are encoded in StaffSeeder.
     */
    public const WIPE_TABLES = [
        // Finance / fees leaves
        'fee_payment_items',
        'fee_payments',
        'student_fees',
        'fee_masters',
        'fee_types',
        'fee_groups',

        // Exams
        'exam_marks',
        'exam_schedules',
        'exam_results',
        'annual_results',
        'exams',
        'grade_rules',

        // Attendance
        'attendance_records',
        'attendance_settings',
        'staff_attendance_records',

        // Homework / activity
        'homework_submissions',
        'homework_assignments',
        'syllabus_topics',
        'syllabi',
        'daily_activity_logs',
        'class_routines',

        // Behavior, health, communication
        'behavior_incidents',
        'health_records',
        'communication_logs',

        // Notifications + CMS
        'in_app_notifications',
        'notification_preferences',
        'notices',
        'cms_announcements',
        'cms_gallery_photos',
        'cms_gallery_albums',
        'cms_pages',
        'cms_sliders',

        // Library / inventory / hostel / transport (light touch — clear if present)
        'book_issues',
        'library_members',

        // Generated docs
        'generated_certificates',

        // Students and guardians (deepest)
        'student_documents',
        'student_promotions',
        'student_guardians',
        'students',
        'student_applications',
        'student_categories',

        // Class structure
        'class_subjects',
        'sections',
        'subjects',
        'classes',

        // Staff
        'staff_payrolls',
        'leave_requests',
        'leave_types',
        'staff_profiles',
        'salary_components',
        'designations',
        'departments',

        // Finance
        'expenses',
        'budgets',
        'expense_categories',

        // Visitors / misc (light)
        'visitors',
    ];

    public function run(): void
    {
        $tenant = tenancy()->tenant;
        if (! $tenant) {
            throw new \RuntimeException('DemoTenantSeeder must run inside tenant context (tenants:run db:seed).');
        }

        // The seeder used to be hard-pinned to TARGET_TENANT_ID (the live AQM
        // school) and refused every other tenant. That is now inverted: any
        // tenant may be seeded, but seeding AQM specifically requires an
        // explicit DEMO_ALLOW_AQM=1 opt-in.
        //
        // Why the inversion: this seeder WIPES operational tables. Pinning it
        // to a real production tenant meant the only thing it could ever
        // destroy was live customer data, and an accidental run on the wrong
        // machine was unrecoverable. AQM now needs a deliberate extra flag,
        // and everything else (demo tenants) just works.
        if ($tenant->id === self::TARGET_TENANT_ID
            && ! $this->detectFlag('--allow-aqm', 'DEMO_ALLOW_AQM')) {
            $this->command?->error(
                "Refusing to seed tenant '{$tenant->id}': that is the live AQM school.\n"
                . '  This seeder wipes operational tables. If you really mean it, '
                . 're-run with DEMO_ALLOW_AQM=1.'
            );
            throw new \RuntimeException('Refusing to seed the AQM tenant without DEMO_ALLOW_AQM=1.');
        }

        // Pick the locale / school-identity profile. Explicit DEMO_PROFILE
        // wins; otherwise the AQM tenant gets Pakistan and anything else
        // gets the US school, which is what a fresh demo tenant wants.
        $profileKey = strtolower(trim((string) getenv('DEMO_PROFILE')));
        if ($profileKey === '') {
            $profileKey = $tenant->id === self::TARGET_TENANT_ID ? 'pak' : 'usa';
        }
        $profile = match ($profileKey) {
            'pak', 'pk' => new \Database\Seeders\Demo\Support\PakProfile(),
            'usa', 'us' => new \Database\Seeders\Demo\Support\UsaProfile(),
            default => throw new \RuntimeException("Unknown DEMO_PROFILE '{$profileKey}' (expected pak|usa)."),
        };
        \Database\Seeders\Demo\Support\DemoProfile::use($profile);

        // Pin Faker / mt_rand for reproducibility.
        mt_srand(20260505);
        if (function_exists('fake')) {
            try {
                fake()->seed(20260505); // @phpstan-ignore-line
            } catch (\Throwable) {
                // Faker version may not support ->seed(); ignore.
            }
        }

        $fresh = $this->detectFlag('--fresh', 'DEMO_FRESH');
        $force = $this->detectFlag('--force', 'DEMO_FORCE');

        $this->command?->info('═══ ' . $profile->school()['name'] . ' demo seeder ═══');
        $this->command?->line('Tenant : ' . $tenant->id);
        $this->command?->line('Profile: ' . $profileKey . ' (' . $profile->school()['currency_code'] . ')');
        $this->command?->line('Mode  : ' . ($fresh ? 'FRESH (wipe + reseed)' : 'CHECK ONLY (refuse if data exists)'));
        $this->command?->line('');

        if (! $fresh) {
            $this->refuseIfDataExists();
        } else {
            $this->printWipeSummary();
            if (! $force && ! $this->confirmWipe()) {
                $this->command?->warn('Aborted by operator.');
                return;
            }
            $this->wipe();
        }

        $this->runSeedPipeline();
        $this->command?->info('');
        $this->command?->info('═══ Seeding complete ═══');
        $this->printPostRunCounts();
    }

    protected function detectFlag(string $argvFlag, string $envVar): bool
    {
        if (in_array(strtolower((string) getenv($envVar)), ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        $argv = $_SERVER['argv'] ?? [];
        return in_array($argvFlag, $argv, true);
    }

    protected function refuseIfDataExists(): void
    {
        $students = DB::table('students')->count();
        if ($students > 0) {
            $msg = "Demo data appears to already exist (students={$students} rows).\n"
                . "  Re-run with DEMO_FRESH=1 (or include --fresh in argv) to wipe and reseed:\n"
                . "    docker exec -e DEMO_FRESH=1 kynexedu-app \\\n"
                . "      php artisan tenants:run db:seed \\\n"
                . "      --argument='class=DemoTenantSeeder' \\\n"
                . "      --tenants=" . self::TARGET_TENANT_ID;
            $this->command?->error($msg);
            throw new \RuntimeException('Refusing to seed over existing data.');
        }
    }

    protected function printWipeSummary(): void
    {
        $this->command?->line('Pre-wipe row counts (operational tables):');
        $rowCounts = [];
        foreach (self::WIPE_TABLES as $table) {
            try {
                $rowCounts[$table] = DB::table($table)->count();
            } catch (\Throwable) {
                $rowCounts[$table] = 'n/a';
            }
        }
        $rowCounts['school_users (non-preserved)'] = DB::table('school_users')
            ->whereNotIn('id', [
                StaffSeeder::ADMIN_USER_ID,
                StaffSeeder::HEAD_USER_ID,
            ])
            ->count();
        $rowCounts['model_has_roles (orphan + non-preserved)'] = DB::table('model_has_roles')
            ->whereNotIn('model_id', [
                StaffSeeder::ADMIN_USER_ID,
                StaffSeeder::HEAD_USER_ID,
            ])
            ->count();
        foreach ($rowCounts as $t => $c) {
            $this->command?->line(sprintf('  %-50s %s', $t, $c));
        }
        $this->command?->line('');
        $this->command?->line('Preserved (NOT wiped):');
        $this->command?->line('  - school_users id=' . StaffSeeder::ADMIN_USER_ID . ' (admin)');
        $this->command?->line('  - school_users id=' . StaffSeeder::HEAD_USER_ID . ' (head)');
        $this->command?->line('  - roles, permissions, role_has_permissions');
        $this->command?->line('  - id_card_templates, certificate_templates, notification_templates');
        $this->command?->line('');
    }

    protected function confirmWipe(): bool
    {
        if (! $this->command) {
            // Non-interactive (e.g. running from a test) and no --force —
            // err on the side of safety.
            return false;
        }
        return $this->command->confirm(
            'Proceed with wipe + reseed of tenant ' . self::TARGET_TENANT_ID . '?',
            false,
        );
    }

    protected function wipe(): void
    {
        $this->command?->line('Wiping operational tables...');
        DB::transaction(function () {
            foreach (self::WIPE_TABLES as $table) {
                try {
                    $deleted = DB::table($table)->delete();
                    if ($deleted > 0) {
                        $this->command?->line("  · {$table}: {$deleted} rows");
                    }
                } catch (\Throwable $e) {
                    $this->command?->warn("  ⚠ {$table} skipped: " . $e->getMessage());
                }
            }

            $deletedUsers = DB::table('school_users')
                ->whereNotIn('id', [
                    StaffSeeder::ADMIN_USER_ID,
                    StaffSeeder::HEAD_USER_ID,
                ])
                ->delete();
            $this->command?->line("  · school_users (non-preserved): {$deletedUsers} rows");

            // Clean orphan role assignments (model_id no longer exists in school_users)
            $orphans = DB::table('model_has_roles')
                ->whereNotIn('model_id', function ($q) {
                    $q->select('id')->from('school_users');
                })
                ->delete();
            $this->command?->line("  · model_has_roles (orphans): {$orphans} rows");
        });
        $this->command?->line('');
    }

    protected function runSeedPipeline(): void
    {
        // Resolve sub-seeders from the container so type-hinted dependencies
        // (StaffSeeder → ClassesSeeder, etc.) are wired automatically. Keep
        // the same instances across calls so userIdByLabel etc. flow.
        $this->command?->line('Running seeders in order...');

        $school = app(SchoolIdentitySeeder::class);
        $school->setCommand($this->command);
        $school->run();

        $staff = app(StaffSeeder::class);
        $staff->setCommand($this->command);
        $staff->run();

        $classes = new ClassesSeeder($staff);
        $classes->setCommand($this->command);
        $classes->run();

        $studentsAndParents = new StudentsAndParentsSeeder($staff, $classes);
        $studentsAndParents->setCommand($this->command);
        $studentsAndParents->run();

        $fees = new FeesSeeder($staff, $classes, $studentsAndParents);
        $fees->setCommand($this->command);
        $fees->run();

        $attendance = new AttendanceSeeder($staff, $classes, $studentsAndParents);
        $attendance->setCommand($this->command);
        $attendance->run();

        $exams = new ExamsAndResultsSeeder($staff, $classes, $studentsAndParents);
        $exams->setCommand($this->command);
        $exams->run();

        // Online exams with AI grading. Runs alongside the lecture content so
        // the assessment bank lines up with the material students were taught.
        $onlineExams = new OnlineExamsSeeder($staff, $classes, $studentsAndParents);
        $onlineExams->setCommand($this->command);
        $onlineExams->run();

        // Lectures, homework lifecycle and AI tutor history. Runs after
        // students and exams so it can attach to real cohorts.
        $lectures = new LecturesAndAssignmentsSeeder($staff, $classes, $studentsAndParents);
        $lectures->setCommand($this->command);
        $lectures->run();

        // The weekly timetable. class_routines is in the wipe list above, so
        // without this the demo school has no schedule at all.
        $timetable = new TimetableSeeder($classes);
        $timetable->setCommand($this->command);
        $timetable->run();

        // The curriculum spine. Runs after lectures so each planned topic can
        // pick up the material already published against it.
        $syllabus = new SyllabusSeeder($staff, $classes);
        $syllabus->setCommand($this->command);
        $syllabus->run();

        $finance = new FinanceSeeder($staff);
        $finance->setCommand($this->command);
        $finance->run();

        $cms = new CmsContentSeeder();
        $cms->setCommand($this->command);
        $cms->run();

        $notifications = new NotificationsSeeder($staff);
        $notifications->setCommand($this->command);
        $notifications->run();

        $idCards = new IdCardsAndCertificatesSeeder($staff, $studentsAndParents);
        $idCards->setCommand($this->command);
        $idCards->run();
    }

    protected function printPostRunCounts(): void
    {
        $rows = [
            'school_users' => DB::table('school_users')->count(),
            'staff_profiles' => DB::table('staff_profiles')->count(),
            'students (active+alumni)' => DB::table('students')->count(),
            'student_guardians' => DB::table('student_guardians')->count(),
            'classes' => DB::table('classes')->count(),
            'sections' => DB::table('sections')->count(),
            'subjects' => DB::table('subjects')->count(),
            'class_subjects' => DB::table('class_subjects')->count(),
            'fee_masters' => DB::table('fee_masters')->count(),
            'student_fees' => DB::table('student_fees')->count(),
            'fee_payments' => DB::table('fee_payments')->count(),
            'attendance_records' => DB::table('attendance_records')->count(),
            'exams' => DB::table('exams')->count(),
            'exam_schedules' => DB::table('exam_schedules')->count(),
            'exam_marks' => DB::table('exam_marks')->count(),
            'exam_results' => DB::table('exam_results')->count(),
            'expenses' => DB::table('expenses')->count(),
            'staff_payrolls' => DB::table('staff_payrolls')->count(),
            'cms_pages' => DB::table('cms_pages')->count(),
            'cms_sliders' => DB::table('cms_sliders')->count(),
            'cms_gallery_albums' => DB::table('cms_gallery_albums')->count(),
            'cms_gallery_photos' => DB::table('cms_gallery_photos')->count(),
            'cms_announcements' => DB::table('cms_announcements')->count(),
            'notices' => DB::table('notices')->count(),
            'in_app_notifications' => DB::table('in_app_notifications')->count(),
            'generated_certificates' => DB::table('generated_certificates')->count(),
        ];
        $this->command?->line('');
        $this->command?->line('Post-run row counts:');
        foreach ($rows as $name => $count) {
            $this->command?->line(sprintf('  %-30s %s', $name, $count));
        }
    }
}
