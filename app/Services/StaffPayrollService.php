<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\StaffPayroll;
use Illuminate\Support\Facades\DB;

/**
 * StaffPayrollService — Handles payroll generation with advisory lock protection.
 *
 * PostgreSQL advisory locks prevent duplicate payroll generation when
 * concurrent queue workers process the same tenant + month.
 */
class StaffPayrollService
{
    /**
     * Generate payroll for a tenant/month, protected by an advisory lock.
     *
     * @throws \RuntimeException If payroll generation is already running for this month.
     */
    public function generatePayroll(string $tenantId, int $month, int $year): int
    {
        return $this->withAdvisoryLock(
            "payroll:{$tenantId}:{$year}-{$month}",
            fn () => $this->doGeneratePayroll($tenantId, $month, $year)
        );
    }

    /**
     * Actual payroll generation logic (called inside advisory lock).
     */
    private function doGeneratePayroll(string $tenantId, int $month, int $year): int
    {
        $profiles = \App\Models\Tenant\StaffProfile::whereHas('schoolUser', function ($q) {
            $q->where('is_active', true);
        })->get();

        $generated = 0;

        DB::transaction(function () use ($profiles, $month, $year, &$generated) {
            foreach ($profiles as $profile) {
                $exists = StaffPayroll::where('staff_profile_id', $profile->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->exists();

                if ($exists) {
                    continue;
                }

                StaffPayroll::create([
                    'staff_profile_id'     => $profile->id,
                    'school_user_id'       => $profile->school_user_id,
                    'month'                => $month,
                    'year'                 => $year,
                    'basic_salary_paisas'  => $profile->basic_salary_paisas ?? 0,
                    'allowances_paisas'    => 0,
                    'deductions_paisas'    => 0,
                    'net_salary_paisas'    => $profile->basic_salary_paisas ?? 0,
                    'status'               => 'draft',
                ]);

                $generated++;
            }
        });

        return $generated;
    }

    /**
     * Execute a callback inside a PostgreSQL advisory lock.
     *
     * @throws \RuntimeException If the lock cannot be acquired (another process holds it).
     */
    private function withAdvisoryLock(string $key, \Closure $callback): mixed
    {
        $lockKey = crc32($key);

        $acquired = DB::selectOne(
            'SELECT pg_try_advisory_lock(?) AS acquired',
            [$lockKey]
        )->acquired;

        if (! $acquired) {
            throw new \RuntimeException(
                "Operation \"{$key}\" is already running. Please wait."
            );
        }

        try {
            return $callback();
        } finally {
            DB::statement('SELECT pg_advisory_unlock(?)', [$lockKey]);
        }
    }
}
