<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditFinding;
use App\Models\AuditRun;
use Illuminate\Console\Command;

class AuditReportCommand extends Command
{
    protected $signature = 'audit:report
        {run          : Audit run UUID}
        {--severity=  : Filter findings by severity (critical|high|medium|low|info|ok)}
        {--type=      : Filter findings by finding_type}
        {--json       : Output raw JSON report file}';

    protected $description = 'Display findings for a completed audit run';

    public function handle(): int
    {
        $runId = $this->argument('run');
        $run   = AuditRun::find($runId);

        if (! $run) {
            $this->error("Audit run not found: {$runId}");
            return self::FAILURE;
        }

        if ($this->option('json')) {
            if (! $run->json_report_path || ! file_exists($run->json_report_path)) {
                $this->error('JSON report file not found for this run.');
                return self::FAILURE;
            }
            $this->line(file_get_contents($run->json_report_path));
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf('Run     : %s', $run->id));
        $this->line(sprintf('Scope   : %s  Tenant: %s  Role: %s', $run->scope, $run->tenant_slug ?? '—', $run->role ?? '—'));
        $this->line(sprintf('Status  : %s', strtoupper($run->status)));
        $this->line(sprintf('Started : %s', $run->started_at?->toDateTimeString()));
        $this->line(sprintf('Finished: %s', $run->finished_at?->toDateTimeString() ?? '—'));

        $this->table(
            ['Severity', 'Count'],
            [
                ['CRITICAL', $run->findings_critical],
                ['HIGH',     $run->findings_high],
                ['MEDIUM',   $run->findings_medium],
                ['LOW',      $run->findings_low],
                ['INFO',     $run->findings_info],
            ],
        );

        $query = AuditFinding::where('run_id', $run->id);

        if ($sev = $this->option('severity')) {
            $query->where('severity', $sev);
        }
        if ($type = $this->option('type')) {
            $query->where('finding_type', $type);
        }

        $findings = $query
            ->orderByRaw("CASE severity
                WHEN 'critical' THEN 1
                WHEN 'high'     THEN 2
                WHEN 'medium'   THEN 3
                WHEN 'low'      THEN 4
                WHEN 'info'     THEN 5
                ELSE 6 END")
            ->orderBy('detected_at')
            ->get();

        if ($findings->isEmpty()) {
            $this->info('No findings match the filter.');
            return self::SUCCESS;
        }

        $this->table(
            ['Sev', 'Type', 'Role', 'URL', 'Title'],
            $findings->map(fn ($f) => [
                strtoupper($f->severity),
                $f->finding_type,
                $f->role,
                mb_strimwidth($f->url,   0, 55, '…'),
                mb_strimwidth($f->title, 0, 75, '…'),
            ])->toArray(),
        );

        return self::SUCCESS;
    }
}
