<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshMaterializedViews extends Command
{
    protected $signature = 'kynex:refresh-materialized-views
                            {--view= : Specific view to refresh (attendance|fees)}';

    protected $description = 'Refresh materialized views across all active tenants';

    private const VIEWS = [
        'attendance' => 'mv_student_attendance_summary',
        'fees'       => 'mv_fee_collection_summary',
    ];

    public function handle(): int
    {
        $viewFilter = $this->option('view');
        $views = $viewFilter
            ? [self::VIEWS[$viewFilter] ?? null]
            : array_values(self::VIEWS);

        $views = array_filter($views);

        if (empty($views)) {
            $this->error("Unknown view: {$viewFilter}. Use 'attendance' or 'fees'.");
            return self::FAILURE;
        }

        $tenants = Tenant::on('central')
            ->where('status', 'active')
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($views, $tenant) {
                if (DB::connection()->getDriverName() !== 'pgsql') {
                    return;
                }

                foreach ($views as $view) {
                    $start = microtime(true);
                    DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY {$view}");
                    $ms = round((microtime(true) - $start) * 1000);
                    $this->info("Refreshed {$view} for tenant {$tenant->id} in {$ms}ms");
                }
            });
        }

        return self::SUCCESS;
    }
}
