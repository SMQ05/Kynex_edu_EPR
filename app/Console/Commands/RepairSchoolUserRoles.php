<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SchoolUser;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Cleans up SchoolUser role state across every tenant:
 *  - Sets active_role to the user's first role when null.
 *  - Reports users with no roles at all so an admin can fix them.
 *
 *   php artisan kynex:repair-school-user-roles
 *   php artisan kynex:repair-school-user-roles --dry-run
 */
class RepairSchoolUserRoles extends Command
{
    protected $signature = 'kynex:repair-school-user-roles
                            {tenant? : Optional tenant id}
                            {--dry-run : Show planned changes without writing}';

    protected $description = 'Backfill missing active_role values on SchoolUsers and report users with no roles';

    public function handle(): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->get()
            : Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenants.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        foreach ($tenants as $tenant) {
            $this->line("");
            $this->line("=== Tenant: {$tenant->id} ===");

            $tenant->run(function () use ($dryRun) {
                $fixed = 0;
                $unrolled = [];

                foreach (SchoolUser::with('roles')->get() as $user) {
                    $roles = $user->roles->pluck('name')->all();

                    if (empty($roles)) {
                        $unrolled[] = "{$user->name} <{$user->email}>";
                        continue;
                    }

                    if ($user->active_role && in_array($user->active_role, $roles, true)) {
                        continue;
                    }

                    // Pick the highest-ranked role as the default active role.
                    $primary = $this->pickPrimary($roles);

                    $this->line(sprintf(
                        '  → %s active_role: %s -> %s (roles: %s)',
                        $user->email,
                        $user->active_role ?? 'null',
                        $primary,
                        implode(',', $roles),
                    ));

                    if (! $dryRun) {
                        $user->forceFill(['active_role' => $primary])->saveQuietly();
                    }
                    $fixed++;
                }

                $this->info("  ✓ {$fixed} active_role values backfilled");

                if (! empty($unrolled)) {
                    $this->warn(sprintf('  ⚠ %d users with NO roles at all:', count($unrolled)));
                    foreach ($unrolled as $line) {
                        $this->line('     - ' . $line);
                    }
                    $this->line('     (assign a role via /admin/school-users/{id}/edit)');
                }
            });
        }

        return self::SUCCESS;
    }

    /**
     * Pick the highest-priority role as the dashboard role. Higher rank
     * roles win, so a user with both TEACHER and INSTITUTE_HEAD lands on
     * the institute head dashboard rather than the teacher one.
     */
    protected function pickPrimary(array $roles): string
    {
        $rank = [
            'MULTI_INSTITUTE_HEAD' => 110,
            'INSTITUTE_HEAD'       => 100,
            'SCHOOL_ADMIN'         => 90,
            'HR_MANAGER'           => 70,
            'REGISTRAR'            => 70,
            'BURSAR'               => 70,
            'EXAM_ADMIN'           => 70,
            'ACCOUNTANT'           => 60,
            'TEACHER'              => 50,
            'TRANSPORT_MANAGER'    => 40,
            'HOSTEL_WARDEN'        => 40,
            'LIBRARIAN'            => 40,
            'ATTENDANCE_CLERK'     => 40,
            'NURSE'                => 40,
            'COUNSELOR'            => 40,
            'CAFETERIA_MANAGER'    => 40,
            'RECEPTIONIST'         => 30,
            'PARENT'               => 20,
            'STUDENT'              => 10,
        ];

        usort($roles, fn ($a, $b) => ($rank[$b] ?? 0) <=> ($rank[$a] ?? 0));
        return $roles[0];
    }
}
