<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiAssistant — generic OpenAI-compatible chat client.
 *
 * Routes to Groq or OpenRouter based on `tenants.ai_provider`. Both
 * providers expose the same chat-completions endpoint shape, so we just
 * swap base URL + auth header.
 *
 * Designed to be the single entry point for every role-aware AI feature
 * in the app (admin assistant, teacher quiz generator, parent translator,
 * student study buddy, etc.). Logs every call into ai_usage_logs and
 * enforces per-tenant monthly budgets.
 */
class AiAssistant
{
    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    public static function forCurrentTenant(): self
    {
        $tenant = tenancy()->tenant;
        if (! $tenant instanceof Tenant) {
            throw new \RuntimeException('AiAssistant requires an active tenant context.');
        }
        return new self($tenant);
    }

    /**
     * Send a chat completion request with optional message history.
     *
     * @param  string                              $systemPrompt
     * @param  array<int, array{role:string,content:string}>  $messages   Conversation history (oldest → newest)
     * @return string                              Model response text
     *
     * @throws \RuntimeException
     */
    public function chat(string $systemPrompt, array $messages, string $feature = 'general_chat'): string
    {
        $this->ensureEnabled();
        $this->ensureWithinBudget();

        $provider = $this->tenant->ai_provider ?: 'openrouter';
        [$baseUrl, $apiKey, $extraHeaders] = $this->resolveEndpoint($provider);
        $model = $this->resolveModel($provider);

        $payload = [
            'model'    => $model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages,
            ),
            'max_tokens'  => (int) config('services.openrouter.max_tokens', 1200),
            'temperature' => 0.7,
        ];

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders($extraHeaders)
                ->timeout(60)
                ->post($baseUrl . '/chat/completions', $payload);

            if (! $response->successful()) {
                Log::error('AI provider error', [
                    'tenant_id' => $this->tenant->id,
                    'provider'  => $provider,
                    'status'    => $response->status(),
                    'body'      => $response->json(),
                ]);
                throw new \RuntimeException(
                    "AI request failed ({$provider}): HTTP " . $response->status()
                );
            }

            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $usage   = $data['usage'] ?? [];

            $cost = $this->estimateCost($usage, $model, $provider);
            $this->logUsage($model, $provider, $usage, $cost, $feature);

            return trim($content);
        } catch (\Throwable $e) {
            Log::error('AI exception', [
                'tenant_id' => $this->tenant->id,
                'provider'  => $provider,
                'message'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Agentic chat with tool/function-calling. Runs a bounded loop: the
     * model may call read tools (executed via $executor), whose results are
     * fed back until it produces a final answer. Falls back to a plain
     * answer if it never stops calling tools.
     *
     * @param  array<int, array{role:string,content:string}>  $messages
     * @param  list<array<string,mixed>>                       $toolSchemas  OpenAI tool definitions
     * @param  callable(string $name, array $args): string     $executor
     */
    public function chatWithTools(
        string $systemPrompt,
        array $messages,
        array $toolSchemas,
        callable $executor,
        string $feature = 'assistant_tools',
        int $maxIterations = 4,
    ): string {
        $this->ensureEnabled();
        $this->ensureWithinBudget();

        $provider = $this->tenant->ai_provider ?: 'openrouter';
        [$baseUrl, $apiKey, $extraHeaders] = $this->resolveEndpoint($provider);
        $model = $this->resolveModel($provider);
        $maxTokens = (int) config('services.openrouter.max_tokens', 1200);

        $convo = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages,
        );

        for ($i = 0; $i < $maxIterations; $i++) {
            $payload = [
                'model'       => $model,
                'messages'    => $convo,
                'max_tokens'  => $maxTokens,
                'temperature' => 0.4,
            ];
            if ($toolSchemas !== []) {
                $payload['tools'] = $toolSchemas;
                $payload['tool_choice'] = 'auto';
            }

            $response = Http::withToken($apiKey)
                ->withHeaders($extraHeaders)
                ->timeout(60)
                ->post($baseUrl . '/chat/completions', $payload);

            if (! $response->successful()) {
                throw new \RuntimeException("AI request failed ({$provider}): HTTP " . $response->status());
            }

            $data = $response->json();
            $message = $data['choices'][0]['message'] ?? [];
            $this->logUsage($model, $provider, $data['usage'] ?? [], $this->estimateCost($data['usage'] ?? [], $model, $provider), $feature);

            $toolCalls = $message['tool_calls'] ?? [];
            if (empty($toolCalls)) {
                return trim((string) ($message['content'] ?? ''));
            }

            // Record the assistant's tool-call turn, then answer each call.
            $convo[] = $message;
            foreach ($toolCalls as $call) {
                $fnName = $call['function']['name'] ?? '';
                $rawArgs = $call['function']['arguments'] ?? '{}';
                $args = is_array($rawArgs) ? $rawArgs : (json_decode((string) $rawArgs, true) ?: []);
                $result = $executor($fnName, is_array($args) ? $args : []);

                $convo[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content'      => is_string($result) ? $result : (string) json_encode($result),
                ];
            }
        }

        // Iteration budget exhausted — ask once more without tools for a
        // final synthesised answer.
        $response = Http::withToken($apiKey)
            ->withHeaders($extraHeaders)
            ->timeout(60)
            ->post($baseUrl . '/chat/completions', [
                'model'       => $model,
                'messages'    => $convo,
                'max_tokens'  => $maxTokens,
                'temperature' => 0.4,
            ]);

        if (! $response->successful()) {
            return 'Sorry, I could not complete that request.';
        }

        $data = $response->json();
        $this->logUsage($model, $provider, $data['usage'] ?? [], $this->estimateCost($data['usage'] ?? [], $model, $provider), $feature);

        return trim((string) ($data['choices'][0]['message']['content'] ?? 'Sorry, I could not complete that request.'));
    }

    /**
     * Pick base URL, API key and auth headers for the chosen provider.
     *
     * @return array{0: string, 1: string, 2: array<string,string>}
     */
    private function resolveEndpoint(string $provider): array
    {
        switch ($provider) {
            case 'groq':
                $apiKey = $this->tenant->ai_openrouter_api_key
                    ?: config('services.groq.api_key', env('GROQ_API_KEY'));
                if (! $apiKey) {
                    throw new \RuntimeException('No Groq API key configured (per-tenant or platform).');
                }
                return [
                    'https://api.groq.com/openai/v1',
                    $apiKey,
                    [],
                ];

            case 'openrouter':
            default:
                $apiKey = $this->tenant->ai_openrouter_api_key
                    ?: config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
                if (! $apiKey) {
                    throw new \RuntimeException('No OpenRouter API key configured (per-tenant or platform).');
                }
                return [
                    'https://openrouter.ai/api/v1',
                    $apiKey,
                    [
                        'HTTP-Referer'       => config('app.url'),
                        'X-OpenRouter-Title' => 'KynexEdu ERP',
                    ],
                ];
        }
    }

    /**
     * Per-provider sane defaults if the configured `ai_model` doesn't
     * exist on that provider.
     */
    private function resolveModel(string $provider): string
    {
        $configured = $this->tenant->ai_model;

        return match ($provider) {
            'groq' => $configured && str_contains($configured, '/')
                ? 'llama-3.3-70b-versatile'   // Groq uses non-prefixed names
                : ($configured ?: 'llama-3.3-70b-versatile'),
            default => $configured ?: 'google/gemini-2.0-flash-001',
        };
    }

    private function ensureEnabled(): void
    {
        if (! $this->tenant->ai_enabled) {
            throw new \RuntimeException(
                "AI is not enabled for {$this->tenant->school_name}. Ask the SaaS admin to enable it under Tenants → AI Settings."
            );
        }
    }

    private function ensureWithinBudget(): void
    {
        $cap = (int) ($this->tenant->ai_monthly_budget_paisas ?? 0);
        if ($cap > 0 && $this->tenant->ai_used_this_month_paisas >= $cap) {
            throw new \RuntimeException(
                "AI monthly budget of " . ($cap / 100) . " PKR exhausted for {$this->tenant->school_name}."
            );
        }
    }

    private function estimateCost(array $usage, string $model, string $provider): int
    {
        // Per-1M-token rates in USD cents. Groq is dramatically cheaper.
        $rates = [
            // OpenRouter common models
            'google/gemini-2.0-flash-001' => ['input' => 0.075, 'output' => 0.30],
            'google/gemini-flash-1.5'     => ['input' => 0.075, 'output' => 0.30],
            'openai/gpt-4o-mini'          => ['input' => 0.15,  'output' => 0.60],
            'openai/gpt-4o'               => ['input' => 2.50,  'output' => 10.0],
            'anthropic/claude-sonnet-4'   => ['input' => 3.00,  'output' => 15.0],
            'deepseek/deepseek-chat'      => ['input' => 0.10,  'output' => 0.40],
            // Groq (free tier; charges shown for budgeting headroom)
            'llama-3.3-70b-versatile'     => ['input' => 0.59,  'output' => 0.79],
            'llama-3.1-8b-instant'        => ['input' => 0.05,  'output' => 0.08],
            'mixtral-8x7b-32768'          => ['input' => 0.24,  'output' => 0.24],
        ];

        $row = $rates[$model] ?? ['input' => 0.10, 'output' => 0.40];

        $promptTokens     = (int) ($usage['prompt_tokens']     ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);

        $usd = ($promptTokens / 1_000_000) * $row['input']
             + ($completionTokens / 1_000_000) * $row['output'];

        // USD → PKR paisas. Approx 280 PKR/USD; 1 PKR = 100 paisas.
        return (int) round($usd * 28000);
    }

    private function logUsage(string $model, string $provider, array $usage, int $costPaisas, string $feature): void
    {
        try {
            AiUsageLog::on('central')->create([
                'tenant_id'             => $this->tenant->id,
                'model_used'            => $model,
                'prompt_tokens'         => $usage['prompt_tokens']     ?? 0,
                'completion_tokens'     => $usage['completion_tokens'] ?? 0,
                'estimated_cost_paisas' => $costPaisas,
                'feature_used'          => $feature,
            ]);
            $this->tenant->increment('ai_used_this_month_paisas', $costPaisas);
        } catch (\Throwable $e) {
            Log::warning('AI usage log failed', ['error' => $e->getMessage()]);
        }
    }
}
