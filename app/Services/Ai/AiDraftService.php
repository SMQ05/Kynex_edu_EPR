<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Generative drafting for any feature: notices, emails/SMS, complaint
 * replies, follow-up messages, report narratives, CMS copy, progress-card
 * comments, etc.
 *
 * Returns plain prose (the draft itself, no preamble) so callers can drop
 * the result straight into a textarea/rich editor. A human always reviews
 * and commits — this is advisory-first.
 *
 * All calls route through AiAssistant (per-tenant model/key, budget,
 * usage logging).
 */
class AiDraftService
{
    /**
     * Draft a piece of text.
     *
     * @param  string                 $instruction  What to write, e.g. "a fee reminder for an overdue invoice".
     * @param  array<string,mixed>    $context      Facts the model should use (name => value).
     * @param  string                 $feature      Usage-log tag, e.g. 'draft_fee_reminder'.
     * @param  array{tone?:string,length?:string,language?:string,channel?:string,format?:string}  $options
     */
    public function draft(string $instruction, array $context, string $feature, array $options = []): string
    {
        $tone     = $options['tone']     ?? 'professional and warm';
        $length   = $options['length']   ?? 'concise';
        $language = $options['language'] ?? 'English';
        $channel  = $options['channel']  ?? null;   // sms|whatsapp|email|notice|null
        $format   = $options['format']   ?? null;   // plain|html|markdown|null

        $constraints = [];
        $constraints[] = "Tone: {$tone}.";
        $constraints[] = "Length: {$length}.";
        $constraints[] = "Language: {$language}.";

        if ($channel === 'sms') {
            $constraints[] = 'This is an SMS: keep it under 320 characters, no markdown, no emojis.';
        } elseif ($channel === 'whatsapp') {
            $constraints[] = 'This is a WhatsApp message: short paragraphs, friendly, minimal formatting.';
        } elseif ($channel === 'email') {
            $constraints[] = 'This is an email body: include a brief greeting and sign-off placeholder.';
        } elseif ($channel === 'notice') {
            $constraints[] = 'This is a school notice: clear heading-less announcement body suitable for a board.';
        }

        if ($format === 'html') {
            $constraints[] = 'Return clean, simple HTML (p, ul, strong only). No <html>/<body> wrappers.';
        } elseif ($format === 'markdown') {
            $constraints[] = 'Return Markdown.';
        } else {
            $constraints[] = 'Return plain text only.';
        }

        $system = "You are a writing assistant inside a school management system. "
            . "Write exactly what is asked and NOTHING else — no preamble like "
            . "\"Sure, here is\", no explanations, no surrounding quotes. "
            . "Use the provided context faithfully; never invent names, dates, "
            . "amounts, or facts that are not given. If a needed detail is "
            . "missing, leave a clear [placeholder]. "
            . implode(' ', $constraints);

        $user = "Write: {$instruction}\n\n" . $this->renderContext($context);

        return AiAssistant::forCurrentTenant()->chat(
            systemPrompt: $system,
            messages:     [['role' => 'user', 'content' => $user]],
            feature:      $feature,
        );
    }

    /**
     * Rewrite/improve existing text (grammar, clarity, tone) without
     * changing its meaning.
     */
    public function refine(string $text, string $instruction, string $feature, array $options = []): string
    {
        return $this->draft(
            instruction: "{$instruction}. Keep the original meaning. Here is the text to revise:\n\"\"\"\n{$text}\n\"\"\"",
            context: [],
            feature: $feature,
            options: $options,
        );
    }

    /**
     * Translate text to a target language (e.g. Urdu ⇄ English) preserving
     * meaning and tone.
     */
    public function translate(string $text, string $targetLanguage, string $feature = 'translate'): string
    {
        return $this->draft(
            instruction: "Translate the following into {$targetLanguage}, preserving tone and meaning:\n\"\"\"\n{$text}\n\"\"\"",
            context: [],
            feature: $feature,
            options: ['language' => $targetLanguage],
        );
    }

    /** @param array<string,mixed> $context */
    private function renderContext(array $context): string
    {
        if ($context === []) {
            return '';
        }

        $lines = ['Context (use these exact facts):'];
        foreach ($context as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $label = ucwords(str_replace('_', ' ', (string) $key));
            $val   = is_scalar($value) ? (string) $value : json_encode($value);
            $lines[] = "- {$label}: {$val}";
        }

        return implode("\n", $lines);
    }
}
