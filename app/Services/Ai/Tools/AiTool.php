<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

/**
 * Base class for an agentic assistant tool (OpenAI-compatible function).
 *
 * Read tools return data; write tools (future, staged) must be RBAC-gated
 * and routed through ApprovalService. Each tool declares the permission it
 * needs — the registry filters by it (admins bypass).
 */
abstract class AiTool
{
    abstract public function name(): string;

    abstract public function description(): string;

    /** JSON-schema "properties" map for the function arguments. */
    abstract public function parameters(): array;

    /** Required argument keys. */
    protected function requiredKeys(): array
    {
        return [];
    }

    /** Permission required to use this tool (null = none). */
    public function requiredPermission(): ?string
    {
        return null;
    }

    /** Whether this tool changes data (write tools are staged/off by default). */
    public function isWrite(): bool
    {
        return false;
    }

    /** Execute the tool. Return a concise string for the model. */
    abstract public function handle(array $args): string;

    /** OpenAI tool schema. */
    public function toSchema(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name'        => $this->name(),
                'description' => $this->description(),
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => (object) $this->parameters(),
                    'required'   => $this->requiredKeys(),
                ],
            ],
        ];
    }
}
