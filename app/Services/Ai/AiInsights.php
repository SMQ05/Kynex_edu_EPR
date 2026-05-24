<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Concerns\ExtractsJson;

/**
 * Turns structured school data into human-readable insight: executive
 * summaries on reports, "explain these numbers", risk narratives,
 * recommendations. Feed it arrays/aggregates you already computed — it
 * never queries the DB itself, so callers control the (permission-scoped)
 * data that goes in.
 */
class AiInsights
{
    use ExtractsJson;

    /**
     * Produce a short narrative summary of some data.
     *
     * @param  array<mixed>|string  $data
     */
    public function summarize(array|string $data, string $instruction, string $feature, array $options = []): string
    {
        $length   = $options['length']   ?? 'a short paragraph (3-5 sentences)';
        $language = $options['language'] ?? 'English';
        $audience = $options['audience'] ?? 'a school administrator';

        $system = "You are a data analyst for a school. Explain the data clearly for {$audience} "
            . "in {$language}, {$length}. Be accurate and specific — cite the actual numbers from the "
            . "data. Do not invent figures. No preamble, return the summary only.";

        $payload = is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $user = "{$instruction}\n\nData:\n{$payload}";

        return AiAssistant::forCurrentTenant()->chat(
            systemPrompt: $system,
            messages:     [['role' => 'user', 'content' => $user]],
            feature:      $feature,
        );
    }

    /**
     * Generate prioritised, actionable recommendations from data.
     *
     * @param  array<mixed>  $data
     * @return list<array{title:string,detail:string,priority:string}>
     */
    public function recommendations(array $data, string $goal, string $feature): array
    {
        $system = <<<PROMPT
You are an operations advisor for a school. Given the data, propose concrete,
prioritised actions toward the stated goal. Reply with JSON ONLY:
{"recommendations": [{"title": "<short>", "detail": "<one sentence, specific>", "priority": "high"|"medium"|"low"}]}
Base everything on the data; do not fabricate. Max 6 recommendations.
PROMPT;

        $user = "Goal: {$goal}\n\nData:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $reply = AiAssistant::forCurrentTenant()->chat(
                systemPrompt: $system,
                messages:     [['role' => 'user', 'content' => $user]],
                feature:      $feature,
            );
            $p = $this->extractJson($reply);
            $out = [];
            foreach (($p['recommendations'] ?? []) as $r) {
                if (! is_array($r)) {
                    continue;
                }
                $priority = in_array(($r['priority'] ?? ''), ['high', 'medium', 'low'], true)
                    ? (string) $r['priority'] : 'medium';
                $out[] = [
                    'title'    => (string) ($r['title'] ?? ''),
                    'detail'   => (string) ($r['detail'] ?? ''),
                    'priority' => $priority,
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
