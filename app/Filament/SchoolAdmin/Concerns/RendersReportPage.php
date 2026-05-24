<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Concerns;

use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiInsights;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RendersReportPage — shared helpers for the read-only "Reports" Filament
 * Pages (Exam Reports, Student Reports). Centralises the two behaviours
 * every report page needs:
 *
 *   - downloadPdf()   → stream a DomPDF-rendered Blade as an attachment
 *   - generateAiSummary() → 🤖 executive-summary narrative via AiInsights,
 *                           gated by AiAvailability (never throws to the UI)
 *
 * The host Page provides the data; this trait stays presentation-only and
 * never queries the database itself.
 */
trait RendersReportPage
{
    /** Holds the latest AI executive summary so the Blade can render it. */
    public ?string $aiSummary = null;

    /** True only when the user has run "Generate Report" and there is data. */
    public bool $isLoaded = false;

    /**
     * Stream a DomPDF download built from a Blade view + payload.
     *
     * @param  array<string, mixed>  $data
     */
    protected function streamPdf(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): StreamedResponse
    {
        $tenant = function_exists('tenant') ? tenant() : null;

        $pdf = Pdf::loadView($view, array_merge($data, ['tenant' => $tenant]))
            ->setPaper($paper, $orientation);

        $bytes = $pdf->output();
        $safe  = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) ?: 'report.pdf';

        return response()->streamDownload(
            function () use ($bytes): void {
                echo $bytes;
            },
            $safe,
            ['Content-Type' => 'application/pdf', 'Content-Length' => (string) strlen($bytes)],
        );
    }

    /**
     * Produce a short executive-summary narrative from already-computed,
     * permission-scoped report data. Fails soft: shows a notification on
     * any error and never bubbles an exception up to the page.
     *
     * @param  array<mixed>|string  $data
     */
    protected function runAiSummary(array|string $data, string $instruction, string $feature): void
    {
        if (! AiAvailability::enabled()) {
            Notification::make()
                ->title('AI is unavailable')
                ->body(AiAvailability::reason() ?? 'AI is turned off for this school.')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->aiSummary = app(AiInsights::class)->summarize(
                data:        $data,
                instruction: $instruction,
                feature:     $feature,
            );

            Notification::make()
                ->title('AI summary ready')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->aiSummary = null;

            Notification::make()
                ->title('Could not generate AI summary')
                ->body('Please try again in a moment.')
                ->danger()
                ->send();
        }
    }
}
