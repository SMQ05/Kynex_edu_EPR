<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditRun;
use Illuminate\Console\Command;

class AuditPruneCommand extends Command
{
    protected $signature = 'audit:prune
        {--days=30  : Delete runs (and findings) older than this many days}
        {--dry-run  : Show what would be deleted without deleting}';

    protected $description = 'Delete audit runs and their findings older than N days';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = AuditRun::on('central')->where('started_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No audit runs older than {$days} days.");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->line(sprintf(
                '[dry-run] Would delete %d run(s) older than %d days (before %s).',
                $count, $days, $cutoff->toDateString()
            ));
            return self::SUCCESS;
        }

        $deleted = 0;
        // Chunk to avoid loading all records into memory or holding long locks.
        // Findings cascade-delete via FK constraint.
        $query->oldest('started_at')->chunkById(50, function ($runs) use (&$deleted) {
            foreach ($runs as $run) {
                if ($run->json_report_path && file_exists($run->json_report_path)) {
                    @unlink($run->json_report_path);
                }
                $run->delete();
                $deleted++;
            }
        });

        $this->info("Pruned {$deleted} audit run(s) and their findings.");
        return self::SUCCESS;
    }
}
