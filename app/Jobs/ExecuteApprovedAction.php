<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ExecuteApprovedAction — Dispatched after an approval request
 * is approved. Runs the registered handler for the action_type.
 */
class ExecuteApprovedAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Map of action_type → handler class (invokable or with handle method).
     * Register new handlers here or via config.
     */
    protected static array $handlers = [];

    public function __construct(
        public ApprovalRequest $approvalRequest,
    ) {}

    /**
     * Register a handler for a given action type.
     */
    public static function registerHandler(string $actionType, string $handlerClass): void
    {
        static::$handlers[$actionType] = $handlerClass;
    }

    public function handle(): void
    {
        $request = $this->approvalRequest;

        if ($request->status !== ApprovalStatus::Approved) {
            Log::warning("ExecuteApprovedAction: Request {$request->id} is not approved, skipping.");
            return;
        }

        $handlerClass = static::$handlers[$request->action_type]
            ?? config("approval.handlers.{$request->action_type}");

        if (! $handlerClass || ! class_exists($handlerClass)) {
            Log::warning("ExecuteApprovedAction: No handler for action_type '{$request->action_type}'.");
            return;
        }

        $handler = app($handlerClass);

        // Initialise tenancy if the request belongs to a tenant — but only
        // if we actually need to (sync queue means we may already be inside
        // a tenant-aware HTTP request; tearing that down breaks the caller).
        $weInitialized = false;
        if ($request->tenant_id) {
            $tenant = \App\Models\Tenant::find($request->tenant_id);
            if ($tenant && (! tenancy()->initialized || tenant()?->id !== $tenant->id)) {
                tenancy()->initialize($tenant);
                $weInitialized = true;
            }
        }

        try {
            if (method_exists($handler, 'handle')) {
                $handler->handle($request);
            } elseif (is_callable($handler)) {
                $handler($request);
            }

            Log::info("ExecuteApprovedAction: Executed handler for '{$request->action_type}' on request {$request->id}.");
        } catch (\Throwable $e) {
            Log::error("ExecuteApprovedAction: Handler failed for request {$request->id}: {$e->getMessage()}");
            throw $e;
        } finally {
            if ($weInitialized && tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }
}
