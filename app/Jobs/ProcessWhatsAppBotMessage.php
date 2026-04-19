<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ProcessWhatsAppBotMessage — Thin wrapper around ProcessWhatsAppMessage.
 *
 * Phase 8 Part 5 spec requires this class name. It accepts the two-argument
 * signature (phone, message) used in the spec and delegates to the full
 * ProcessWhatsAppMessage implementation, which handles tenant resolution,
 * intent detection, and reply sending.
 */
class ProcessWhatsAppBotMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly string $phone,
        private readonly string $message,
    ) {}

    public function handle(): void
    {
        ProcessWhatsAppMessage::dispatchSync(
            $this->phone,
            $this->message,
            'bot',
        );
    }
}
