<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\InfixImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Processes an InfixEdu data import in the background.
 *
 * Progress is stored in cache so the Filament page can poll for updates.
 */
class ProcessInfixImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes max
    public int $tries = 1;

    public function __construct(
        protected string $tenantId,
        protected string $cacheKey = 'infix_import_progress',
    ) {}

    public function handle(): void
    {
        $this->updateProgress('running', 'Starting import...', 0);

        try {
            $service = new InfixImportService();

            $result = $service->import(function (int $current, int $total, string $step) {
                $percent = (int) round(($current / $total) * 100);
                $this->updateProgress('running', "Step {$current}/{$total}: {$step}", $percent);
            });

            $this->updateProgress('completed', 'Import completed successfully', 100, $result);

            Log::info('InfixImport completed', [
                'tenant' => $this->tenantId,
                'stats'  => $result['stats'],
            ]);
        } catch (\Throwable $e) {
            $this->updateProgress('failed', "Import failed: {$e->getMessage()}", 0);

            Log::error('InfixImport failed', [
                'tenant' => $this->tenantId,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
        }
    }

    protected function updateProgress(string $status, string $message, int $percent, ?array $result = null): void
    {
        Cache::put($this->cacheKey, [
            'status'  => $status,
            'message' => $message,
            'percent' => $percent,
            'result'  => $result,
            'updated' => now()->toDateTimeString(),
        ], now()->addHours(1));
    }
}
