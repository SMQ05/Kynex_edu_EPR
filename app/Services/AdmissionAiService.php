<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\GradeAdmissionAnswersBatch;
use App\Models\Tenant\AdmissionTest;
use App\Models\Tenant\AdmissionTestAnswer;
use App\Models\Tenant\AdmissionTestAttempt;
use App\Models\Tenant\AdmissionTestQuestion;
use App\Services\Ai\AiAssistant;
use Illuminate\Support\Facades\Log;

/**
 * AI features for admission tests:
 *   - generateQuestions(): turn a passage of source content into a
 *     set of draft questions (MCQ, True/False, short, essay, math).
 *   - gradeAnswer(): score a single short/math/essay answer against
 *     the question prompt + optional reference answer.
 *   - gradePendingForAttempt(): bulk-grade all unmarked auto-graded
 *     answers in a single attempt.
 *
 * All calls go through the existing AiAssistant which handles tenant
 * provider routing, budget enforcement, and usage logging.
 */
class AdmissionAiService
{
    /**
     * Ask the AI for a JSON list of questions, then insert them as
     * AdmissionTestQuestion rows under $test. Returns the count
     * actually created.
     *
     * @param  array<string,int>  $counts  e.g. ['mcq' => 5, 'true_false' => 2, …]
     */
    public function generateQuestions(
        AdmissionTest $test,
        string $source,
        array $counts,
        string $difficulty = 'medium',
    ): int {
        $totalRequested = array_sum($counts);
        if ($totalRequested <= 0) {
            return 0;
        }

        $system = <<<PROMPT
You are an exam question generator for a school's admission test. You produce
strict JSON ONLY, no commentary, no markdown fences. Each question must be
high quality, fair, and answerable from the provided source content alone.

Output schema (JSON object):
{
  "questions": [
    {
      "type": "mcq" | "true_false" | "short_answer" | "essay" | "math",
      "question": "...",
      "options": ["...", "...", ...],   // ONLY when type=mcq, 3-5 items
      "correct_answer": "A",            // mcq: letter A/B/C/...
                                        // true_false: "true" or "false"
                                        // short_answer/math: expected answer text
                                        // essay: omit or empty string
      "marks": 1
    }
  ]
}

Rules:
- Distribute the requested counts exactly. Do not exceed any limit.
- For MCQ: provide 4 distinct, plausible options and one clearly correct one.
  Number them implicitly — only return the option strings; we'll letter them.
  In correct_answer use the LETTER (A, B, C, D) matching the index of the
  correct option in the options array (A = index 0).
- For math: write standalone math problems answerable as a number or
  short expression. correct_answer is the expected numeric/symbolic answer.
- For essay: ask one open-ended question, leave correct_answer empty.
- Keep marks consistent: MCQ/TF/short = 1 mark each unless noted, essay = 5.
PROMPT;

        $user = "Source content:\n---\n" . trim($source) . "\n---\n\n"
            . "Counts requested:\n"
            . sprintf("  mcq: %d\n  true_false: %d\n  short_answer: %d\n  essay: %d\n  math: %d\n",
                (int) ($counts['mcq']          ?? 0),
                (int) ($counts['true_false']   ?? 0),
                (int) ($counts['short_answer'] ?? 0),
                (int) ($counts['essay']        ?? 0),
                (int) ($counts['math']         ?? 0),
            )
            . "\nDifficulty: {$difficulty}\n"
            . "Return JSON only.";

        $reply = AiAssistant::forCurrentTenant()->chat(
            systemPrompt: $system,
            messages:     [['role' => 'user', 'content' => $user]],
            feature:      'admission_test_generation',
        );

        $payload = $this->extractJson($reply);
        $items   = $payload['questions'] ?? [];

        if (! is_array($items) || $items === []) {
            throw new \RuntimeException('AI returned no questions. Try again with longer source content.');
        }

        $created = 0;
        $sortBase = (int) ($test->questions()->max('sort_order') ?? 0);

        foreach ($items as $i => $q) {
            $type = $q['type'] ?? 'mcq';
            if (! in_array($type, ['mcq', 'true_false', 'short_answer', 'essay', 'math'], true)) {
                continue;
            }

            $options = null;
            $correctAnswer = $q['correct_answer'] ?? null;

            if ($type === 'mcq') {
                $rawOpts = $q['options'] ?? [];
                if (! is_array($rawOpts) || count($rawOpts) < 2) {
                    continue;
                }
                $options = [];
                $idx = 0;
                foreach ($rawOpts as $opt) {
                    $text = trim((string) $opt);
                    if ($text === '') continue;
                    $options[chr(65 + $idx)] = $text;
                    $idx++;
                }
                $correctAnswer = is_string($correctAnswer)
                    ? strtoupper(trim($correctAnswer))
                    : null;
                if (! $correctAnswer || ! isset($options[$correctAnswer])) {
                    // Fall back to first option if AI returned an unusable key.
                    $correctAnswer = array_key_first($options);
                }
            } elseif ($type === 'true_false') {
                $correctAnswer = strtolower(trim((string) ($correctAnswer ?? '')));
                if (! in_array($correctAnswer, ['true', 'false'], true)) {
                    $correctAnswer = 'true';
                }
            } elseif ($type === 'essay') {
                $correctAnswer = null;
            } else {
                $correctAnswer = is_string($correctAnswer) ? trim($correctAnswer) : null;
            }

            AdmissionTestQuestion::create([
                'admission_test_id' => $test->id,
                'type'              => $type,
                'question_text'     => trim((string) ($q['question'] ?? '')),
                'options'           => $options,
                'correct_answer'    => $correctAnswer,
                'marks'             => (float) ($q['marks'] ?? ($type === 'essay' ? 5 : 1)),
                'sort_order'        => $sortBase + $i + 1,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Grade a single short/math/essay answer with AI. Returns
     * ['marks' => float, 'feedback' => string].
     */
    public function gradeAnswer(AdmissionTestQuestion $question, ?string $applicantAnswer): array
    {
        $applicantAnswer = trim((string) ($applicantAnswer ?? ''));

        if ($applicantAnswer === '') {
            return ['marks' => 0.0, 'feedback' => 'No answer provided.'];
        }

        $system = <<<PROMPT
You are a fair, strict exam marker. You ALWAYS reply with a single JSON object
and nothing else:

{
  "marks": <number between 0 and the maximum>,
  "feedback": "<one-sentence rationale, max 200 chars>"
}

Mark partial credit when the answer is partially correct. Be consistent.
For math: accept algebraically equivalent answers.
For short answer: accept synonyms and reasonable paraphrases of the reference.
For essay: assess relevance, coherence, factual accuracy, and clarity.
PROMPT;

        $user = "Question: {$question->question_text}\n"
            . "Type: {$question->type}\n"
            . "Maximum marks: {$question->marks}\n"
            . ($question->correct_answer ? "Reference answer: {$question->correct_answer}\n" : '')
            . "Applicant's answer: {$applicantAnswer}\n"
            . "Return JSON only.";

        try {
            $reply = AiAssistant::forCurrentTenant()->chat(
                systemPrompt: $system,
                messages:     [['role' => 'user', 'content' => $user]],
                feature:      'admission_test_grading',
            );

            $payload = $this->extractJson($reply);
            $marks   = (float) ($payload['marks'] ?? 0);
            $marks   = max(0.0, min((float) $question->marks, $marks));
            $fb      = (string) ($payload['feedback'] ?? '');

            return ['marks' => $marks, 'feedback' => $fb];
        } catch (\Throwable $e) {
            Log::warning('AdmissionAiService::gradeAnswer failed', [
                'error'       => $e->getMessage(),
                'question_id' => $question->id,
            ]);
            return ['marks' => 0.0, 'feedback' => 'Grading unavailable: ' . $e->getMessage()];
        }
    }

    /**
     * Dispatch batched AI grading for an attempt using the queue.
     * Each batch processes $batchSize answers with a 10-second gap
     * so the AI API is not hit all at once.
     */
    public function dispatchBatchedGrading(AdmissionTestAttempt $attempt, int $batchSize = 5): void
    {
        GradeAdmissionAnswersBatch::dispatch($attempt->id, 0, $batchSize);
    }

    /**
     * Run AI grading over every answer in the attempt that is missing
     * marks_awarded AND belongs to a question whose type isn't already
     * fully auto-graded by the rule-based path. Updates the attempt's
     * obtained_marks afterward.
     *
     * For large sets prefer dispatchBatchedGrading() so the AI API
     * is not flooded in one synchronous call.
     *
     * Returns the number of answers actually graded.
     */
    public function gradePendingForAttempt(AdmissionTestAttempt $attempt): int
    {
        $attempt->loadMissing(['answers.question', 'test']);

        $graded = 0;
        foreach ($attempt->answers as $answer) {
            if ($answer->marks_awarded !== null) {
                continue;
            }
            $question = $answer->question;
            if (! $question) {
                continue;
            }
            // Only AI-grade types where the rule-based path didn't already
            // land a verdict.
            if (! in_array($question->type, ['short_answer', 'essay', 'math'], true)) {
                continue;
            }

            $result = $this->gradeAnswer($question, $answer->answer_text);
            $answer->update([
                'marks_awarded' => $result['marks'],
                'is_correct'    => $result['marks'] >= ((float) $question->marks * 0.6),
                'answer_text'   => $answer->answer_text, // unchanged; keep for audit
            ]);
            $graded++;
        }

        // Recompute attempt totals.
        $obtained = (float) $attempt->answers()->sum('marks_awarded');
        $totalMarks = (float) $attempt->test->total_marks;
        $percentage = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : null;

        $stillPending = $attempt->answers()
            ->whereNull('marks_awarded')
            ->exists();

        $attempt->update([
            'obtained_marks'       => $obtained,
            'percentage'           => $percentage,
            'needs_manual_grading' => $stillPending,
            'status'               => $stillPending ? 'submitted' : 'graded',
            'graded_at'            => $stillPending ? null : now(),
        ]);

        // Push the latest score back to the application + scoring service
        // so auto-decision can re-run.
        $app = $attempt->application;
        if ($app) {
            $app->update(['entry_test_score' => $obtained]);
            app(AdmissionScoringService::class)->evaluate($app->refresh());
        }

        return $graded;
    }

    /**
     * Best-effort JSON extractor — strips ```json fences and any
     * leading/trailing prose the model occasionally adds.
     */
    protected function extractJson(string $text): array
    {
        $trim = trim($text);

        // Strip markdown fences if present.
        if (preg_match('/```(?:json)?\s*(.+?)```/s', $trim, $m)) {
            $trim = $m[1];
        }

        // Find the outermost JSON object.
        $start = strpos($trim, '{');
        $end   = strrpos($trim, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new \RuntimeException('AI did not return valid JSON.');
        }

        $candidate = substr($trim, $start, $end - $start + 1);
        $decoded   = json_decode($candidate, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('AI JSON could not be parsed.');
        }

        return $decoded;
    }
}
