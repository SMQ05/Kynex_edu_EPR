<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\CustomReport;
use App\Services\ReportBuilderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * RunScheduledReport — Runs a scheduled custom report, exports it,
 * and emails the file to the configured schedule_email address.
 */
class RunScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public CustomReport $report,
    ) {}

    public function handle(ReportBuilderService $service): void
    {
        try {
            // Export the report as CSV (lightweight for email)
            $filePath = $service->export($this->report, 'csv');

            // Email the file
            if ($this->report->schedule_email) {
                Mail::raw(
                    "Scheduled report \"{$this->report->name}\" is attached.\n\nGenerated: " . now()->format('d M Y, h:i A'),
                    function ($message) use ($filePath) {
                        $message->to($this->report->schedule_email)
                            ->subject("Scheduled Report: {$this->report->name}")
                            ->attach($filePath, [
                                'as'   => basename($filePath),
                                'mime' => 'text/csv',
                            ]);
                    }
                );
            }

            // Update last run timestamp
            $this->report->update(['last_run_at' => now()]);

            Log::info("RunScheduledReport: Successfully ran and emailed report '{$this->report->name}' to {$this->report->schedule_email}");

            // Clean up the file after sending
            if (file_exists($filePath)) {
                unlink($filePath);
            }

        } catch (\Throwable $e) {
            Log::error("RunScheduledReport: Failed for report '{$this->report->name}': {$e->getMessage()}");
            throw $e;
        }
    }
}
