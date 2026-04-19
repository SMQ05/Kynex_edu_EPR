<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generate monthly invoices for all active tenants.
 *
 * Runs on the 1st of each month (via schedule).
 * Calculates: base + (per_student × active_student_count) + usage overages.
 *
 * Invoice number format: KNX-{YYYY}-{MM}-{tenant_slug}
 */
class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'billing:generate-invoices
                            {--month= : Month number (1-12), defaults to current}
                            {--year= : Year, defaults to current}
                            {--tenant= : Generate for a specific tenant ID only}
                            {--dry-run : Show what would be generated without creating}';

    protected $description = 'Generate monthly SaaS billing invoices for all active tenants';

    public function handle(): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year  = (int) ($this->option('year') ?: now()->year);
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        $this->info("Generating invoices for {$periodStart->format('F Y')}");

        if ($dryRun) {
            $this->warn('DRY RUN — no invoices will be created.');
        }

        $query = Tenant::on('central')
            ->whereIn('status', [TenantStatus::Active->value, TenantStatus::Trial->value])
            ->whereNotNull('plan_id');

        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No eligible tenants found.');
            return self::SUCCESS;
        }

        $generated = 0;
        $skipped   = 0;
        $errors    = 0;

        foreach ($tenants as $tenant) {
            try {
                // Skip if invoice already exists for this period
                $exists = Invoice::where('tenant_id', $tenant->id)
                    ->whereDate('period_start', $periodStart)
                    ->exists();

                if ($exists) {
                    $this->line("  ⏭ {$tenant->school_name} — already invoiced");
                    $skipped++;
                    continue;
                }

                // Skip trial tenants whose trial hasn't ended
                if ($tenant->status === TenantStatus::Trial && $tenant->trial_ends_at?->isAfter($periodEnd)) {
                    $this->line("  ⏭ {$tenant->school_name} — still on trial");
                    $skipped++;
                    continue;
                }

                $plan = $tenant->plan;

                if (! $plan) {
                    $this->warn("  ⚠ {$tenant->school_name} — no plan assigned");
                    $skipped++;
                    continue;
                }

                // Calculate amounts
                $activeStudents     = $tenant->active_student_count ?? 0;
                $basePaisas         = $plan->base_price_paisas ?? 0;
                $perStudentPaisas   = ($plan->per_student_price_paisas ?? 0) * $activeStudents;
                $smsUsagePaisas     = 0; // TODO: Calculate from usage logs
                $whatsappUsagePaisas = 0; // TODO: Calculate from usage logs
                $aiUsagePaisas      = $tenant->ai_used_this_month_paisas ?? 0;
                $storageOveragePaisas = $this->calculateStorageOverage($tenant, $plan);
                $discountPaisas     = 0; // TODO: Apply any discount coupons

                $totalPaisas = $basePaisas
                    + $perStudentPaisas
                    + $smsUsagePaisas
                    + $whatsappUsagePaisas
                    + $aiUsagePaisas
                    + $storageOveragePaisas
                    - $discountPaisas;

                // Generate invoice number
                $tenantSlug = Str::slug($tenant->school_name ?? $tenant->id);
                $invoiceNumber = sprintf('KNX-%04d-%02d-%s', $year, $month, $tenantSlug);

                if ($dryRun) {
                    $totalPkr = number_format($totalPaisas / 100, 2);
                    $this->line("  📄 {$tenant->school_name} — PKR {$totalPkr} ({$activeStudents} students)");
                    $generated++;
                    continue;
                }

                Invoice::create([
                    'tenant_id'                 => $tenant->id,
                    'invoice_number'            => $invoiceNumber,
                    'period_start'              => $periodStart,
                    'period_end'                => $periodEnd,
                    'active_student_count'      => $activeStudents,
                    'base_amount_paisas'        => $basePaisas,
                    'per_student_amount_paisas' => $perStudentPaisas,
                    'sms_usage_paisas'          => $smsUsagePaisas,
                    'whatsapp_usage_paisas'     => $whatsappUsagePaisas,
                    'ai_usage_paisas'           => $aiUsagePaisas,
                    'storage_overage_paisas'    => $storageOveragePaisas,
                    'discount_paisas'           => $discountPaisas,
                    'total_paisas'              => max(0, $totalPaisas),
                    'status'                    => InvoiceStatus::Draft,
                ]);

                // Reset AI usage counter for the tenant
                $tenant->update(['ai_used_this_month_paisas' => 0]);

                $totalPkr = number_format($totalPaisas / 100, 2);
                $this->info("  ✅ {$tenant->school_name} — PKR {$totalPkr} ({$invoiceNumber})");
                $generated++;

            } catch (\Throwable $e) {
                $this->error("  ❌ {$tenant->school_name} — {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$generated} generated, {$skipped} skipped, {$errors} errors");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Calculate storage overage charges.
     */
    protected function calculateStorageOverage(Tenant $tenant, SubscriptionPlan $plan): int
    {
        $usedMb = $tenant->storage_used_mb ?? 0;
        $limitGb = $plan->storage_limit_gb ?? 0;
        $limitMb = $limitGb * 1024;

        if ($usedMb <= $limitMb || $limitMb === 0) {
            return 0;
        }

        $overageMb = $usedMb - $limitMb;
        // Charge 50 paisas per MB overage (= PKR 0.50/MB)
        return $overageMb * 50;
    }
}
