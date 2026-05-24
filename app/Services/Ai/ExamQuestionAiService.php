<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Tenant\ExamQuestion;
use App\Models\Tenant\OnlineExamAttempt;
use App\Models\Tenant\QuestionGroup;
use App\Services\Ai\Concerns\ExtractsJson;
use Illuminate\Support\Facades\Log;

/**
 * AI features for the exam question bank + online exams. Mirrors the proven
 * AdmissionAiService pattern (JSON-schema system prompt -> chat() ->
 * extractJson() -> write DB) but targets enrolled-student exams.
 *
 *   - generateQuestions(): turn source content into ExamQuestion rows.
 *   - gradeAnswer(): AI-score a single short/essay/math answer.
 *   - gradePendingForAttempt(): bulk-grade an OnlineExamAttempt.
 *
 * NOTE on integrity policy: AI auto-grading is ALLOWED for exams
 * (only homework/assignment auto-grading is forbidden).
 *
 * All calls route through AiAssistant for per-tenant key/model/budget.
 */
class ExamQuestionAiService
{
    use ExtractsJson;

    /**
     * Generate questions from $source and insert them as ExamQuestion rows
     * inside $group. Returns the count created.
     *
     * @param  array<string,int>  $counts  e.g. ['mcq' => 5, 'true_false' => 2, …]
     */
    public function generateQuestions(
        QuestionGroup $group,
        string $source,
        array $counts,
        string $difficulty = 'medium',
    ): int {
        $totalRequested = array_sum($counts);
        if ($totalRequested <= 0) {
            return 0;
        }

        $system = <<<PROMPT
You are an exam question generator for a school. You produce strict JSON ONLY,
no commentary, no markdown fences. Each question must be high quality, fair,
and answerable from the provided source content alone.

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
      "explanation": "...",             // brief rationale for the correct answer
      "marks": 1
    }
  ]
}

Rules:
- Distribute the requested counts exactly. Do not exceed any limit.
- For MCQ: provide 4 distinct, plausible options and one clearly correct one.
  In correct_answer use the LETTER (A, B, C, D) matching the index of the
  correct option in the options array (A = index 0).
- For math: standalone problems answerable as a number or short expression.
- For essay: one open-ended question, leave correct_answer empty.
- Keep marks consistent: MCQ/TF/short = 1 mark each, essay = 5.
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
            feature:      'exam_question_generation',
        );

        $payload = $this->extractJson($reply);
        $items   = $payload['questions'] ?? [];

        if (! is_array($items) || $items === []) {
            throw new \RuntimeException('AI returned no questions. Try again with longer source content.');
        }

        $created = 0;

        foreach ($items as $q) {
            $type = $q['type'] ?? 'mcq';
            if (! in_array($type, ['mcq', 'true_false', 'short_answer', 'essay', 'math'], true)) {
                continue;
            }

            $options       = null;
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
                    if ($text === '') {
                        continue;
                    }
                    $options[chr(65 + $idx)] = $text;
                    $idx++;
                }
                $correctAnswer = is_string($correctAnswer) ? strtoupper(trim($correctAnswer)) : null;
                if (! $correctAnswer || ! isset($options[$correctAnswer])) {
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

            ExamQuestion::create([
                'question_group_id' => $group->id,
                'subject_id'        => $group->subject_id,
                'type'              => $type,
                'difficulty'        => $difficulty,
                'question_text'     => trim((string) ($q['question'] ?? '')),
                'options'           => $options,
                'correct_answer'    => $correctAnswer,
                'explanation'       => isset($q['explanation']) ? trim((string) $q['explanation']) : null,
                'marks'             => (float) ($q['marks'] ?? ($type === 'essay' ? 5 : 1)),
                'is_active'         => true,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * AI-grade a single short/math/essay answer. Returns
     * ['marks' => float, 'feedback' => string].
     */
    public function gradeAnswer(ExamQuestion $question, ?string $studentAnswer): array
    {
        $studentAnswer = trim((string) ($studentAnswer ?? ''));

        if ($studentAnswer === '') {
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
            . "Student's answer: {$studentAnswer}\n"
            . "Return JSON only.";

        try {
            $reply = AiAssistant::forCurrentTenant()->chat(
                systemPrompt: $system,
                messages:     [['role' => 'user', 'content' => $user]],
                feature:      'exam_answer_grading',
            );

            $payload = $this->extractJson($reply);
            $marks   = (float) ($payload['marks'] ?? 0);
            $marks   = max(0.0, min((float) $question->marks, $marks));
            $fb      = (string) ($payload['feedback'] ?? '');

            return ['marks' => $marks, 'feedback' => $fb];
        } catch (\Throwable $e) {
            Log::warning('ExamQuestionAiService::gradeAnswer failed', [
                'error'       => $e->getMessage(),
                'question_id' => $question->id,
            ]);

            return ['marks' => 0.0, 'feedback' => 'Grading unavailable: ' . $e->getMessage()];
        }
    }

    /**
     * AI-grade every pending short/essay/math answer in the attempt, then
     * recompute totals. Returns the number of answers graded.
     */
    public function gradePendingForAttempt(OnlineExamAttempt $attempt): int
    {
        $attempt->loadMissing(['answers.question', 'onlineExam']);

        $graded = 0;
        foreach ($attempt->answers as $answer) {
            if ($answer->marks_awarded !== null) {
                continue;
            }
            $question = $answer->question;
            if (! $question) {
                continue;
            }
            if (! in_array($question->type, ['short_answer', 'essay', 'math'], true)) {
                continue;
            }

            $result = $this->gradeAnswer($question, $answer->answer_text);
            $answer->update([
                'marks_awarded' => $result['marks'],
                'is_correct'    => $result['marks'] >= ((float) $question->marks * 0.6),
                'ai_feedback'   => $result['feedback'],
            ]);
            $graded++;
        }

        $obtained   = (float) $attempt->answers()->sum('marks_awarded');
        $totalMarks = (float) ($attempt->onlineExam->total_marks ?? 0);
        $percentage = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : null;

        $stillPending = $attempt->answers()->whereNull('marks_awarded')->exists();

        $attempt->update([
            'obtained_marks'       => $obtained,
            'percentage'           => $percentage,
            'needs_manual_grading' => $stillPending,
            'status'               => $stillPending ? 'submitted' : 'graded',
            'graded_at'            => $stillPending ? null : now(),
        ]);

        return $graded;
    }
}
