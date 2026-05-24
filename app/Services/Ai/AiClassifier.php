<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Concerns\ExtractsJson;

/**
 * Classification, routing, scoring and sentiment for free text — used to
 * auto-categorise complaints, route admission enquiries, score leads,
 * gauge urgency/sentiment, etc.
 *
 * Advisory by default: callers should treat results as suggestions a
 * human can accept or override.
 */
class AiClassifier
{
    use ExtractsJson;

    /**
     * Pick the single best label for $text from a fixed list.
     *
     * @param  list<string>  $labels
     * @return array{label:string,confidence:float,reason:string}
     */
    public function classify(string $text, array $labels, string $feature, string $what = 'text'): array
    {
        $labels = array_values(array_filter(array_map('trim', $labels)));
        if ($labels === []) {
            return ['label' => '', 'confidence' => 0.0, 'reason' => 'No labels provided.'];
        }

        $list = implode('", "', $labels);

        $system = <<<PROMPT
You are a precise classifier. Choose the SINGLE best-fitting label for the
given {$what} from this exact list: ["{$list}"].
Reply with JSON ONLY:
{"label": "<one of the provided labels exactly>", "confidence": <0..1>, "reason": "<max 160 chars>"}
You must return one of the provided labels verbatim. Never invent a new label.
PROMPT;

        try {
            $reply = AiAssistant::forCurrentTenant()->chat(
                systemPrompt: $system,
                messages:     [['role' => 'user', 'content' => trim($text)]],
                feature:      $feature,
            );
            $p = $this->extractJson($reply);

            $label = (string) ($p['label'] ?? '');
            // Snap to the closest valid label (case-insensitive) to be safe.
            if (! in_array($label, $labels, true)) {
                foreach ($labels as $valid) {
                    if (strcasecmp($valid, $label) === 0) {
                        $label = $valid;
                        break;
                    }
                }
            }
            if (! in_array($label, $labels, true)) {
                $label = $labels[0];
            }

            return [
                'label'      => $label,
                'confidence' => max(0.0, min(1.0, (float) ($p['confidence'] ?? 0.5))),
                'reason'     => (string) ($p['reason'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['label' => $labels[0], 'confidence' => 0.0, 'reason' => 'AI unavailable: ' . $e->getMessage()];
        }
    }

    /**
     * Score $text on a 0..100 scale for some quality (e.g. lead intent,
     * urgency, risk). Returns score + short rationale + a coarse band.
     *
     * @return array{score:int,band:string,reason:string}
     */
    public function score(string $text, string $criterion, string $feature): array
    {
        $system = <<<PROMPT
You rate text on a 0-100 scale for: {$criterion}.
Reply with JSON ONLY:
{"score": <0..100 integer>, "band": "low" | "medium" | "high", "reason": "<max 160 chars>"}
Be consistent and calibrated.
PROMPT;

        try {
            $reply = AiAssistant::forCurrentTenant()->chat(
                systemPrompt: $system,
                messages:     [['role' => 'user', 'content' => trim($text)]],
                feature:      $feature,
            );
            $p = $this->extractJson($reply);

            $score = (int) round((float) ($p['score'] ?? 0));
            $score = max(0, min(100, $score));
            $band  = in_array(($p['band'] ?? ''), ['low', 'medium', 'high'], true)
                ? (string) $p['band']
                : ($score >= 67 ? 'high' : ($score >= 34 ? 'medium' : 'low'));

            return ['score' => $score, 'band' => $band, 'reason' => (string) ($p['reason'] ?? '')];
        } catch (\Throwable $e) {
            return ['score' => 0, 'band' => 'low', 'reason' => 'AI unavailable: ' . $e->getMessage()];
        }
    }

    /**
     * Sentiment of $text.
     *
     * @return array{sentiment:string,score:float,reason:string}  sentiment ∈ positive|neutral|negative
     */
    public function sentiment(string $text, string $feature = 'sentiment'): array
    {
        $r = $this->classify($text, ['positive', 'neutral', 'negative'], $feature, 'feedback sentiment');

        return [
            'sentiment' => $r['label'] ?: 'neutral',
            'score'     => $r['confidence'],
            'reason'    => $r['reason'],
        ];
    }
}
