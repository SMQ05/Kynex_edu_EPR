<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PrunePiiAccessLog — Deletes old PII audit records beyond retention period.
 *
 * Default retention: 2555 days (≈ 7 years) per FERPA requirements.
 */
class PrunePiiAccessLog extends Command
{
    protected $signature = 'kynex:prune-pii-log
                            {--days=2555 : Retention in days (default 7 years / 2555 days)}';

    protected $description = 'Delete PII access log records older than the retention period';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $tenants = Tenant::on('central')
            ->where('status', 'active')
            ->get();

        $totalDeleted = 0;

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($days, $tenant, &$totalDeleted) {
                if (DB::connection()->getDriverName() !== 'pgsql') {
                    return;
                }

                $deleted = DB::table('pii_access_log')
                    ->where('accessed_at', '<', now()->subDays($days))
                    ->delete();

                if ($deleted > 0) {
                    $totalDeleted += $deleted;
                    $this->info("Tenant {$tenant->id}: pruned {$deleted} records older than {$days} days");
                }
            });
        }

        $this->info("Total pruned: {$totalDeleted} records across all tenants.");

        return self::SUCCESS;
    }
}
