<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\AdmissionTestAttempt;
use App\Services\AdmissionAiService;
use App\Services\AdmissionScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process AI grading for a slice of answers in one attempt.
 *
 * Each dispatch handles $batchSize pending answers starting at $offset.
 * If more ungraded answers remain after the batch, the job re-dispatches
 * itself with the next offset so the AI is never hit with the full set
 * at once.
 */
class GradeAdmissionAnswersBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $attemptId,
        public readonly int $offset = 0,
        public readonly int $batchSize = 5,
    ) {}

    public function handle(AdmissionAiService $aiService, AdmissionScoringService $scoringService): void
    {
        $attempt = AdmissionTestAttempt::with(['answers.question', 'test', 'application'])
            ->find($this->attemptId);

        if (! $attempt) {
            return;
        }

        // Collect all answers that still need AI grading.
        $pending = $attempt->answers->filter(function ($answer) {
            if ($answer->marks_awarded !== null) {
                return false;
            }
            return in_array($answer->question?->type, ['short_answer', 'essay', 'math'], true);
        })->values();

        if ($pending->isEmpty()) {
            $this->finaliseIfComplete($attempt, $scoringService);
            return;
        }

        $batch = $pending->slice($this->offset, $this->batchSize);

        foreach ($batch as $answer) {
            try {
                $result = $aiService->gradeAnswer($answer->question, $answer->answer_text);
                $answer->update([
                    'marks_awarded' => $result['marks'],
                    'is_correct'    => $result['marks'] >= ((float) $answer->question->marks * 0.6),
                ]);
            } catch (\Throwable $e) {
                Log::warning('GradeAdmissionAnswersBatch: answer grading failed', [
                    'answer_id' => $answer->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $nextOffset = $this->offset + $this->batchSize;

        if ($nextOffset < $pending->count()) {
            // More answers remain — dispatch next batch after a short delay
            // to avoid hammering the AI API.
            static::dispatch($this->attemptId, $nextOffset, $this->batchSize)
                ->delay(now()->addSeconds(10));
        } else {
            $this->finaliseIfComplete($attempt->fresh(['answers']), $scoringService);
        }
    }

    private function finaliseIfComplete(AdmissionTestAttempt $attempt, AdmissionScoringService $scoringService): void
    {
        $stillPending = $attempt->answers()
            ->whereNull('marks_awarded')
            ->whereHas('question', fn ($q) => $q->whereIn('type', ['short_answer', 'essay', 'math']))
            ->exists();

        $obtained = (float) $attempt->answers()->sum('marks_awarded');
        $totalMarks = (float) $attempt->test->total_marks;
        $percentage = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : null;

        $attempt->update([
            'obtained_marks'       => $obtained,
            'percentage'           => $percentage,
            'needs_manual_grading' => $stillPending,
            'status'               => $stillPending ? 'submitted' : 'graded',
            'graded_at'            => $stillPending ? null : now(),
        ]);

        $app = $attempt->application;
        if ($app) {
            $app->update(['entry_test_score' => $obtained]);
            $scoringService->evaluate($app->refresh());
        }
    }
}
