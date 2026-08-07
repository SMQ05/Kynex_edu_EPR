<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\Tenant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenRouterService — Routes AI requests through OpenRouter API.
 *
 * OpenRouter.ai is an aggregator offering 100+ AI models via a single
 * OpenAI-compatible API. KynexEdu pays centrally; schools are billed
 * monthly based on usage logs stored in the central DB.
 *
 * Default model: google/gemini-flash-1.5 (~$0.075/1M tokens).
 *
 * @throws \RuntimeException if AI is not enabled or budget is exceeded.
 */
class OpenRouterService
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * Cost per 1M tokens for common models (input, output) in USD cents.
     *
     * NOTE: the two google/gemini-* ids previously listed here
     * (gemini-flash-1.5, gemini-2.0-flash-001) have been retired by
     * OpenRouter and 404 if requested — verified against
     * GET /api/v1/models. They are removed so nothing can select them.
     * A model missing from this table still works; it just falls back to
     * the conservative default rate below, which under-counts spend for
     * expensive models — so keep any model offered to tenants listed here.
     */
    private const MODEL_COSTS = [
        'openai/gpt-4o-mini'            => ['input' => 0.15, 'output' => 0.60],
        'openai/gpt-4o'                 => ['input' => 2.50, 'output' => 10.0],
        'anthropic/claude-haiku-4.5'    => ['input' => 1.00, 'output' => 5.00],
        'anthropic/claude-sonnet-4'     => ['input' => 3.00, 'output' => 15.0],
        'anthropic/claude-sonnet-4.5'   => ['input' => 3.00, 'output' => 15.0],
        'deepseek/deepseek-chat'        => ['input' => 0.10, 'output' => 0.40],
    ];

    private ?PendingRequest $http = null;

    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    /**
     * Send a chat completion request.
     *
     * @param  string  $systemPrompt   System prompt defining the AI role
     * @param  string  $userMessage   The user's question or request
     * @param  string  $context        Additional context (appended to user message)
     * @return string                  The model's response text
     *
     * @throws \RuntimeException if AI is disabled or budget exhausted
     */
    public function chat(
        string $systemPrompt,
        string $userMessage,
        string $context = '',
    ): string {
        $this->ensureEnabled();
        $this->ensureWithinBudget();

        // Fall back to the configured platform default rather than a
        // hardcoded id. The previous hardcoded 'google/gemini-flash-1.5'
        // has been retired by OpenRouter and now 404s, so any tenant that
        // never picked a model got a failed request instead of an answer.
        $model     = $this->tenant->ai_model
            ?: config('platform_apis.ai.default_model', 'openai/gpt-4o-mini');
        // These read platform_apis.ai.*, not services.openrouter.* — there is
        // no services.openrouter config block, so the old keys always resolved
        // to their inline defaults. That silently capped every answer at 1000
        // tokens instead of the configured 4096.
        $apiKey    = $this->tenant->ai_openrouter_api_key
            ?: config('platform_apis.ai.api_key') ?: env('OPENROUTER_API_KEY');
        $maxTokens = (int) config('platform_apis.ai.max_tokens', 4096);

        $fullUserMessage = $context !== ''
            ? $context."\n\n".$userMessage
            : $userMessage;

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'HTTP-Referer'      => config('app.url'),
                    'X-OpenRouter-Title' => 'KynexEdu ERP',
                ])
                ->timeout(60)
                ->post(self::BASE_URL.'/chat/completions', [
                    'model'    => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $fullUserMessage],
                    ],
                    'max_tokens' => $maxTokens,
                ]);

            if (! $response->successful()) {
                Log::error('OpenRouter API error', [
                    'tenant_id' => $this->tenant->id,
                    'status'    => $response->status(),
                    'body'      => $response->json(),
                ]);
                throw new \RuntimeException('OpenRouter API request failed: '.$response->status());
            }

            $data       = $response->json();
            $usage      = $data['usage'] ?? [];
            $content    = $data['choices'][0]['message']['content'] ?? '';
            $cost       = $this->estimateCost($usage, $model);
            $feature    = $this->inferFeature($systemPrompt);

            // Log usage to central DB
            $this->logUsage($model, $usage, $cost, $feature);

            return $content;

        } catch (\Throwable $e) {
            Log::error('OpenRouter exception', [
                'tenant_id' => $this->tenant->id,
                'message'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Ensure AI is enabled for this tenant.
     *
     * @throws \RuntimeException
     */
    private function ensureEnabled(): void
    {
        if (! $this->tenant->ai_enabled) {
            throw new \RuntimeException(
                "AI is not enabled for school: {$this->tenant->school_name}"
            );
        }
    }

    /**
     * Ensure the tenant has not exceeded their monthly AI budget.
     *
     * @throws \RuntimeException
     */
    private function ensureWithinBudget(): void
    {
        if ($this->tenant->ai_used_this_month_paisas >= $this->tenant->ai_monthly_budget_paisas) {
            throw new \RuntimeException(
                "AI budget exhausted for school: {$this->tenant->school_name}"
            );
        }
    }

    /**
     * Estimate the cost in paisas from OpenRouter usage data.
     */
    private function estimateCost(array $usage, string $model): int
    {
        $inputCostPerM  = self::MODEL_COSTS[$model]['input']  ?? 0.10;
        $outputCostPerM = self::MODEL_COSTS[$model]['output'] ?? 0.40;

        $promptTokens     = $usage['prompt_tokens']     ?? 0;
        $completionTokens = $usage['completion_tokens']  ?? 0;

        $usd = ($promptTokens / 1_000_000) * $inputCostPerM
             + ($completionTokens / 1_000_000) * $outputCostPerM;

        // Convert USD to paisas (1 PKR ≈ 0.0035 USD; simplified: paisas = USD * 100 * 35 ≈ USD * 2800)
        return (int) round($usd * 2800);
    }

    /**
     * Log AI usage to the central ai_usage_logs table.
     */
    private function logUsage(string $model, array $usage, int $costPaisas, string $feature): void
    {
        AiUsageLog::on('central')->create([
            'tenant_id'            => $this->tenant->id,
            'model_used'          => $model,
            'prompt_tokens'       => $usage['prompt_tokens']     ?? 0,
            'completion_tokens'   => $usage['completion_tokens']  ?? 0,
            'estimated_cost_paisas' => $costPaisas,
            'feature_used'       => $feature,
        ]);

        // Increment tenant's monthly usage counter
        $this->tenant->increment('ai_used_this_month_paisas', $costPaisas);
    }

    /**
     * Infer the feature context from the system prompt keywords.
     */
    private function inferFeature(string $systemPrompt): string
    {
        $prompt = strtolower($systemPrompt);

        return match (true) {
            str_contains($prompt, 'admission')    => 'admission_assistant',
            str_contains($prompt, 'parent')       => 'parent_portal_guide',
            str_contains($prompt, 'overview'),
            str_contains($prompt, 'dashboard')   => 'admin_overview',
            str_contains($prompt, 'fee')          => 'fee_advisor',
            str_contains($prompt, 'report')      => 'report_generator',
            default                               => 'general_chat',
        };
    }
}
