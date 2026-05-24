<?php

declare(strict_types=1);

namespace App\Services\Ai\Concerns;

/**
 * Shared best-effort JSON extraction for AI replies.
 *
 * LLMs occasionally wrap JSON in ```json fences or add leading/trailing
 * prose. This trait pulls the outermost JSON value (object or array) out
 * of a raw completion string and decodes it. Lifted from the proven
 * AdmissionAiService::extractJson() so every AI service parses replies
 * the same way.
 */
trait ExtractsJson
{
    /**
     * Extract and decode the outermost JSON object/array from an AI reply.
     *
     * @return array<mixed>
     *
     * @throws \RuntimeException when no valid JSON can be parsed.
     */
    protected function extractJson(string $text): array
    {
        $trim = trim($text);

        // Strip a markdown code fence if present.
        if (preg_match('/```(?:json)?\s*(.+?)```/s', $trim, $m)) {
            $trim = trim($m[1]);
        }

        // Locate the outermost JSON value — object {...} or array [...].
        $objStart = strpos($trim, '{');
        $arrStart = strpos($trim, '[');

        $start = match (true) {
            $objStart === false => $arrStart,
            $arrStart === false => $objStart,
            default             => min($objStart, $arrStart),
        };

        if ($start === false) {
            throw new \RuntimeException('AI did not return valid JSON.');
        }

        $open  = $trim[$start];
        $close = $open === '{' ? '}' : ']';
        $end   = strrpos($trim, $close);

        if ($end === false || $end <= $start) {
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
