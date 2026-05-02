<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Campus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * For each tenant: reassign every campus_id-bearing row to that tenant's
 * main campus, then soft-delete the other campuses. Run after we
 * restricted campus creation to SaaS / institute-level — existing test
 * data should collapse to one campus per school.
 *
 *   php artisan kynex:consolidate-campuses           # all tenants
 *   php artisan kynex:consolidate-campuses demo      # one tenant
 *   php artisan kynex:consolidate-campuses --dry-run
 */
class ConsolidateCampuses extends Command
{
    protected $signature = 'kynex:consolidate-campuses
                            {tenant? : Optional tenant id}
                            {--dry-run : Show planned changes without writing}';

    protected $description = 'Reassign dependents to the main campus and soft-delete other campuses, per tenant';

    /**
     * Tables that hold a campus_id column. Discovered live from
     * information_schema so we don't miss any plugin-added tables.
     */
    protected array $cachedDependentTables = [];

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
            $this->line('');
            $this->line("=== Tenant: {$tenant->id} ===");
            try {
                $tenant->run(function () use ($dryRun) {
                    $this->processCurrentTenant($dryRun);
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$e->getMessage()}");
            }
        }

        $this->line('');
        return self::SUCCESS;
    }

    protected function processCurrentTenant(bool $dryRun): void
    {
        $campuses = Campus::query()->orderBy('created_at')->get();

        if ($campuses->isEmpty()) {
            $this->line('  No campuses — skipping.');
            return;
        }

        if ($campuses->count() === 1) {
            $only = $campuses->first();
            if (! $only->is_main_campus) {
                $this->line("  Single campus '{$only->name}' — flagging is_main_campus=true");
                if (! $dryRun) {
                    $only->forceFill(['is_main_campus' => true])->save();
                }
            } else {
                $this->line("  Single campus '{$only->name}' already main — nothing to do.");
            }
            return;
        }

        $main = $campuses->firstWhere('is_main_campus', true) ?? $campuses->first();

        if (! $main->is_main_campus && ! $dryRun) {
            $main->forceFill(['is_main_campus' => true])->save();
        }

        $this->line("  Main campus: '{$main->name}' ({$main->id})");

        $secondary = $campuses->where('id', '!=', $main->id);

        $tables = $this->discoverCampusTables();

        foreach ($secondary as $sec) {
            $this->line("  → Migrating dependents from '{$sec->name}' ({$sec->id})");

            $totalMoved = 0;
            foreach ($tables as $table) {
                $count = DB::table($table)->where('campus_id', $sec->id)->count();
                if ($count === 0) {
                    continue;
                }

                $this->line(sprintf('     %-28s → %s rows', $table, $count));
                $totalMoved += $count;

                if (! $dryRun) {
                    DB::table($table)->where('campus_id', $sec->id)->update(['campus_id' => $main->id]);
                }
            }

            if ($totalMoved === 0) {
                $this->line('     (no dependents)');
            }

            if (! $dryRun) {
                $sec->delete(); // soft delete
                $this->info("     ✓ '{$sec->name}' soft-deleted");
            } else {
                $this->line("     [dry-run] would soft-delete '{$sec->name}'");
            }
        }
    }

    /**
     * @return array<int,string> Tables in this tenant's schema that have
     *                            a campus_id column.
     */
    protected function discoverCampusTables(): array
    {
        $rows = DB::select("
            SELECT table_name
            FROM information_schema.columns
            WHERE column_name = 'campus_id'
              AND table_schema = current_schema()
            ORDER BY table_name
        ");

        return array_values(array_map(fn ($r) => $r->table_name, $rows));
    }
}
