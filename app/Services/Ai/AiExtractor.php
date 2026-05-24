<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Concerns\ExtractsJson;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Structured data extraction from documents — bank payment slips,
 * admission/postal letters, certificates, forms. Two paths:
 *
 *   - extract():       extract fields from text you already have.
 *   - extractFromFile(): pull text from a PDF (via smalot/pdfparser) or
 *                        send an image to a vision model, then extract.
 *
 * Returns an associative array shaped by the $schema you pass. Always
 * confirm critical values (amounts, dates) with a human — OCR/vision is
 * not infallible.
 */
class AiExtractor
{
    use ExtractsJson;

    /**
     * Extract fields described by $schema from $text.
     *
     * @param  array<string,string>  $schema  field => description, e.g.
     *                                         ['amount' => 'paid amount as a number',
     *                                          'paid_on' => 'payment date YYYY-MM-DD',
     *                                          'reference' => 'transaction/reference number']
     * @return array<string,mixed>
     */
    public function extract(string $text, array $schema, string $feature): array
    {
        $fields = [];
        foreach ($schema as $key => $desc) {
            $fields[] = "  \"{$key}\": <{$desc}, or null if absent>";
        }
        $schemaBlock = "{\n" . implode(",\n", $fields) . "\n}";

        $system = <<<PROMPT
You extract structured data from a document. Reply with JSON ONLY in exactly
this shape:
{$schemaBlock}
Rules: use null when a field is not clearly present. Never guess amounts,
dates, or reference numbers. Dates as YYYY-MM-DD. Numbers without currency
symbols or thousands separators.
PROMPT;

        $reply = AiAssistant::forCurrentTenant()->chat(
            systemPrompt: $system,
            messages:     [['role' => 'user', 'content' => "Document:\n\"\"\"\n" . trim($text) . "\n\"\"\""]],
            feature:      $feature,
        );

        $decoded = $this->extractJson($reply);

        // Only return keys we asked for.
        $out = [];
        foreach (array_keys($schema) as $key) {
            $out[$key] = $decoded[$key] ?? null;
        }

        return $out;
    }

    /**
     * Extract text from a stored file, then run extract(). PDFs are parsed
     * locally; images are sent to a vision model.
     *
     * @param  array<string,string>  $schema
     * @return array<string,mixed>
     */
    public function extractFromFile(string $absolutePath, array $schema, string $feature): array
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            $text = $this->pdfText($absolutePath);

            return $this->extract($text, $schema, $feature);
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return $this->extractFromImage($absolutePath, $schema, $feature);
        }

        // Plain text / other readable formats.
        $text = (string) @file_get_contents($absolutePath);

        return $this->extract($text, $schema, $feature);
    }

    private function pdfText(string $path): string
    {
        try {
            return (new PdfParser())->parseFile($path)->getText();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not read PDF text: ' . $e->getMessage());
        }
    }

    /**
     * Vision extraction: post an image (as a data URL) to the tenant's
     * configured OpenAI-compatible endpoint. Mirrors AiAssistant's
     * provider resolution so it respects per-tenant keys.
     *
     * @param  array<string,string>  $schema
     * @return array<string,mixed>
     */
    private function extractFromImage(string $path, array $schema, string $feature): array
    {
        $tenant = tenancy()->tenant;
        if (! $tenant) {
            throw new \RuntimeException('AiExtractor requires an active tenant context.');
        }
        if (! $tenant->ai_enabled) {
            throw new \RuntimeException('AI is not enabled for this school.');
        }

        $bytes = (string) @file_get_contents($path);
        if ($bytes === '') {
            throw new \RuntimeException('Image file is empty or unreadable.');
        }
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            default => 'image/jpeg',
        };
        $dataUrl = "data:{$mime};base64," . base64_encode($bytes);

        $fields = [];
        foreach ($schema as $key => $desc) {
            $fields[] = "  \"{$key}\": <{$desc}, or null if absent>";
        }
        $schemaBlock = "{\n" . implode(",\n", $fields) . "\n}";

        $system = "You read the image (a scanned document/slip) and extract data. "
            . "Reply with JSON ONLY in exactly this shape:\n{$schemaBlock}\n"
            . "Use null when not clearly present. Never guess amounts/dates/reference numbers.";

        // Vision needs a multimodal model; prefer a sensible default.
        $model = $tenant->ai_model && str_contains((string) $tenant->ai_model, 'gpt-4o')
            ? $tenant->ai_model
            : 'openai/gpt-4o-mini';

        $apiKey = $tenant->ai_openrouter_api_key
            ?: config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
        if (! $apiKey) {
            throw new \RuntimeException('No OpenRouter API key configured for vision extraction.');
        }

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer'       => config('app.url'),
                'X-OpenRouter-Title' => 'KynexEdu ERP',
            ])
            ->timeout(90)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'Extract the fields from this document image.'],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ]],
                ],
                'max_tokens'  => 800,
                'temperature' => 0,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Vision extraction failed: HTTP ' . $response->status());
        }

        $data    = $response->json();
        $content = (string) ($data['choices'][0]['message']['content'] ?? '');

        // Best-effort usage logging so vision calls also count toward budget.
        $this->logVisionUsage($tenant, $model, $data['usage'] ?? [], $feature);

        $decoded = $this->extractJson($content);
        $out = [];
        foreach (array_keys($schema) as $key) {
            $out[$key] = $decoded[$key] ?? null;
        }

        return $out;
    }

    private function logVisionUsage(\App\Models\Tenant $tenant, string $model, array $usage, string $feature): void
    {
        try {
            \App\Models\AiUsageLog::on('central')->create([
                'tenant_id'             => $tenant->id,
                'model_used'            => $model,
                'prompt_tokens'         => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens'     => (int) ($usage['completion_tokens'] ?? 0),
                'estimated_cost_paisas' => (int) round((($usage['total_tokens'] ?? 0) / 1_000_000) * 28000 * 1.0),
                'feature_used'          => $feature,
            ]);
        } catch (\Throwable) {
            // best effort
        }
    }
}
