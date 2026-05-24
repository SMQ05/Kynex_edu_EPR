<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Support;

use App\Services\Ai\AiContentDetector;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Reusable "Check originality" Filament action (PREMIUM, OPT-IN).
 *
 * Runs the configured AiContentDetector over a record's text field and reports
 * an advisory AI-written likelihood. It is ADVISORY ONLY — it never grades, and
 * it does not write a verdict back to the record. This keeps it within the
 * guide.md academic-integrity policy (no AI auto-grading of homework).
 *
 * NOT wired into any resource yet. To attach it later — e.g. to a
 * HomeworkSubmission relation manager / table — add to that table's actions:
 *
 *   use App\Filament\SchoolAdmin\Support\CheckOriginalityAction;
 *   ...
 *   ->actions([
 *       CheckOriginalityAction::make('submission_text'),
 *       // ... existing actions
 *   ])
 *
 * The button auto-hides unless the school has opted in + configured a provider
 * (AiContentDetector::enabledFor()).
 *
 * @see \App\Services\Ai\AiContentDetector
 */
class CheckOriginalityAction
{
    /**
     * @param  string  $textField  The record attribute holding the text to scan
     *                             (e.g. 'submission_text' on HomeworkSubmission).
     */
    public static function make(string $textField = 'submission_text', string $name = 'checkOriginality'): Action
    {
        return Action::make($name)
            ->label('Check originality')
            ->icon('heroicon-o-shield-check')
            ->color('warning')
            ->visible(fn (): bool => AiContentDetector::enabledFor(tenancy()->tenant ?? null))
            ->requiresConfirmation()
            ->modalHeading('AI-originality check')
            ->modalDescription('Advisory only. This estimates how likely the text was AI-written. It does NOT grade the submission.')
            ->modalSubmitActionLabel('Run check')
            ->action(function ($record) use ($textField): void {
                $text = trim((string) ($record->{$textField} ?? ''));

                if ($text === '') {
                    Notification::make()
                        ->title('Nothing to check')
                        ->body('This record has no text content to scan.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = AiContentDetector::forCurrentTenant()->detect($text);

                if (! ($result['available'] ?? false)) {
                    Notification::make()
                        ->title('Originality check unavailable')
                        ->body($result['message'] ?? 'AI-originality detection is not configured for this school.')
                        ->warning()
                        ->send();

                    return;
                }

                $aiPct = (int) round(($result['ai_score'] ?? 0) * 100);
                $class = (string) ($result['classification'] ?? 'unknown');

                $color = match ($class) {
                    'ai'    => 'danger',
                    'mixed' => 'warning',
                    default => 'success',
                };

                $note = Notification::make()
                    ->title("AI-written likelihood: {$aiPct}%")
                    ->body('Classification: ' . ucfirst($class) . ' — advisory only, please review manually.');

                match ($color) {
                    'danger'  => $note->danger(),
                    'warning' => $note->warning(),
                    default   => $note->success(),
                };

                $note->send();
            });
    }
}
