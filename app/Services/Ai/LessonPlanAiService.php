<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Concerns\ExtractsJson;

/**
 * Generates lesson-plan content (objectives + activities + resources +
 * assessment) from a topic/lesson title and context. Reuses the shared
 * AiAssistant chat client and the ExtractsJson trait. Tenant-budget aware
 * (enforced inside AiAssistant). Advisory: output is inserted into the form
 * for a human to review before saving.
 */
class LessonPlanAiService
{
    use ExtractsJson;

    /**
     * @param  array{subject?:string,class?:string,duration_minutes?:int|string|null,grade_level?:string,language?:string}  $context
     * @return array{objectives:string,activities:string,teaching_resources:string,assessment:string,homework:string}
     */
    public function generate(string $topic, array $context = [], string $feature = 'lesson_plan_generate'): array
    {
        $subject  = (string) ($context['subject'] ?? '');
        $class    = (string) ($context['class'] ?? $context['grade_level'] ?? '');
        $duration = $context['duration_minutes'] ?? null;
        $language = (string) ($context['language'] ?? 'English');

        $system = <<<PROMPT
You are an experienced curriculum designer creating a single classroom lesson plan.
Write age-appropriate, practical content a teacher can use directly. Reply with JSON ONLY:
{
  "objectives": "<3-5 measurable learning objectives, one per line>",
  "activities": "<sequenced teaching activities with rough timings, one per line>",
  "teaching_resources": "<materials, references and aids needed, one per line>",
  "assessment": "<how learning is checked in/after the lesson, one per line>",
  "homework": "<a short follow-up task for students>"
}
Keep it concrete and classroom-ready. Do not invent a different topic. Write in {$language}.
PROMPT;

        $details = array_filter([
            "Topic: {$topic}",
            $subject !== '' ? "Subject: {$subject}" : null,
            $class !== '' ? "Class/Grade: {$class}" : null,
            $duration ? "Lesson length: {$duration} minutes" : null,
        ]);

        $user = implode("\n", $details);

        $reply = AiAssistant::forCurrentTenant()->chat(
            systemPrompt: $system,
            messages:     [['role' => 'user', 'content' => $user]],
            feature:      $feature,
        );

        $p = $this->extractJson($reply);

        return [
            'objectives'         => (string) ($p['objectives'] ?? ''),
            'activities'         => (string) ($p['activities'] ?? ''),
            'teaching_resources' => (string) ($p['teaching_resources'] ?? ''),
            'assessment'         => (string) ($p['assessment'] ?? ''),
            'homework'           => (string) ($p['homework'] ?? ''),
        ];
    }
}
