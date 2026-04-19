<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * PaymentGatewayInterface — Contract for all payment gateway drivers.
 *
 * All amounts are in paisas (integer).
 */
interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier (e.g., 'jazzcash', 'easypaisa', 'stripe').
     */
    public function getGatewayName(): string;

    /**
     * Initiate a payment request.
     *
     * @param  int     $amountPaisas  Amount in paisas
     * @param  string  $orderId       Unique order/receipt reference
     * @param  array   $meta          Additional metadata (student info, return URLs, etc.)
     * @return array{success: bool, redirect_url: ?string, transaction_id: ?string, error: ?string}
     */
    public function initiatePayment(int $amountPaisas, string $orderId, array $meta = []): array;

    /**
     * Verify / confirm a payment after callback.
     *
     * @param  array  $payload  The callback/webhook payload from the gateway
     * @return array{verified: bool, transaction_id: ?string, amount_paisas: ?int, status: string, raw: array}
     */
    public function verifyPayment(array $payload): array;

    /**
     * Initiate a refund for a completed payment.
     *
     * @return array{success: bool, refund_id: ?string, error: ?string}
     */
    public function refund(string $transactionId, int $amountPaisas): array;

    /**
     * Check if this gateway is currently configured and usable.
     */
    public function isConfigured(): bool;
}
