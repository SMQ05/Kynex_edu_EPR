<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Str;

/**
 * Stripe Payment Gateway Stub.
 *
 * TODO: Implement actual Stripe Checkout / Payment Intents integration.
 * Docs: https://stripe.com/docs/payments
 */
class StripeGateway implements PaymentGatewayInterface
{
    public function getGatewayName(): string
    {
        return 'stripe';
    }

    public function initiatePayment(int $amountPaisas, string $orderId, array $meta = []): array
    {
        // TODO: Implement Stripe Checkout Session or Payment Intent
        // 1. Create Stripe\Checkout\Session with line_items
        // 2. Set success_url and cancel_url from $meta
        // 3. Return the checkout session URL

        return [
            'success'        => false,
            'redirect_url'   => null,
            'transaction_id' => 'STR-' . Str::random(12),
            'error'          => 'Stripe gateway not yet implemented. Configure STRIPE_KEY and STRIPE_SECRET in .env.',
        ];
    }

    public function verifyPayment(array $payload): array
    {
        // TODO: Verify Stripe webhook signature (stripe-signature header)
        // Parse event type (checkout.session.completed, payment_intent.succeeded)
        // Extract payment_intent ID and amount

        return [
            'verified'       => false,
            'transaction_id' => $payload['id'] ?? null,
            'amount_paisas'  => isset($payload['amount']) ? (int) $payload['amount'] : null,
            'status'         => 'unverified',
            'raw'            => $payload,
        ];
    }

    public function refund(string $transactionId, int $amountPaisas): array
    {
        // TODO: Stripe\Refund::create(['payment_intent' => $transactionId, 'amount' => $amountPaisas])

        return [
            'success'   => false,
            'refund_id' => null,
            'error'     => 'Stripe refund not yet implemented.',
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.stripe.key'))
            && ! empty(config('services.stripe.secret'));
    }
}
