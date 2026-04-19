<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

/**
 * JazzCash Payment Gateway — Full MIGS/HTTP Redirect integration.
 *
 * Uses HMAC-SHA256 secure hash for request integrity.
 * Docs: https://sandbox.jazzcash.com.pk/
 */
class JazzCashGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $password;
    protected string $integritySalt;
    protected string $endpoint;
    protected string $returnUrl;

    public function __construct()
    {
        $this->merchantId    = config('services.jazzcash.merchant_id', '');
        $this->password      = config('services.jazzcash.password', '');
        $this->integritySalt = config('services.jazzcash.integrity_salt', '');

        $env = config('services.jazzcash.env', 'sandbox');
        $this->endpoint = $env === 'production'
            ? 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform'
            : 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform';

        $this->returnUrl = config('services.jazzcash.return_url', '');
    }

    public function getGatewayName(): string
    {
        return 'jazzcash';
    }

    /**
     * Initiate a JazzCash payment via HTTP redirect form POST.
     *
     * @param  int     $amountPaisas  Amount in paisas
     * @param  string  $orderId       Unique order/receipt reference
     * @param  array   $meta          Additional metadata (tenant_slug, callback_url, return_url, description, etc.)
     * @return array{success: bool, redirect_url: ?string, transaction_id: ?string, form_fields: ?array, error: ?string}
     */
    public function initiatePayment(int $amountPaisas, string $orderId, array $meta = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success'        => false,
                'redirect_url'   => null,
                'transaction_id' => null,
                'error'          => 'JazzCash gateway not configured. Set credentials in .env.',
            ];
        }

        $tenantSlug = $meta['tenant_slug'] ?? 'default';
        $txnRefNo   = 'JC-' . $tenantSlug . '-' . time();
        $txnDateTime       = now()->format('YmdHis');
        $txnExpiryDateTime = now()->addHour()->format('YmdHis');

        // JazzCash expects amount as string without decimals (in paisas)
        $ppAmount = (string) $amountPaisas;

        $callbackUrl = $meta['callback_url'] ?? url('/payment/jazzcash/callback');
        $returnUrl   = $meta['return_url'] ?? $this->returnUrl ?: url('/payment/jazzcash/return');

        $params = [
            'pp_Amount'            => $ppAmount,
            'pp_BillReference'     => $meta['bill_reference'] ?? 'FEE',
            'pp_Description'       => $meta['description'] ?? 'Fee Payment',
            'pp_Language'          => 'EN',
            'pp_MerchantID'        => $this->merchantId,
            'pp_Password'          => $this->password,
            'pp_ReturnURL'         => $returnUrl,
            'pp_TxnCurrency'       => 'PKR',
            'pp_TxnDateTime'       => $txnDateTime,
            'pp_TxnExpiryDateTime' => $txnExpiryDateTime,
            'pp_TxnRefNo'          => $txnRefNo,
            'pp_TxnType'           => 'MWALLET',
            'pp_Version'           => '1.1',
            'ppmpf_1'              => $meta['student_id'] ?? '',
            'ppmpf_2'              => $meta['student_name'] ?? '',
            'ppmpf_3'              => $meta['fee_payment_id'] ?? '',
            'ppmpf_4'              => '',
            'ppmpf_5'              => '',
        ];

        // Generate secure hash
        $params['pp_SecureHash'] = $this->generateHash($params);

        Log::info('JazzCash: Payment initiated', [
            'txn_ref'  => $txnRefNo,
            'amount'   => $ppAmount,
            'order_id' => $orderId,
        ]);

        return [
            'success'        => true,
            'redirect_url'   => $this->endpoint,
            'transaction_id' => $txnRefNo,
            'form_fields'    => $params,
            'error'          => null,
        ];
    }

    /**
     * Verify a JazzCash callback by recomputing the secure hash.
     *
     * @param  array  $payload  The callback/webhook payload from JazzCash
     * @return array{verified: bool, transaction_id: ?string, amount_paisas: ?int, status: string, raw: array}
     */
    public function verifyPayment(array $payload): array
    {
        $receivedHash = $payload['pp_SecureHash'] ?? '';
        $responseCode = $payload['pp_ResponseCode'] ?? '';
        $txnRefNo     = $payload['pp_TxnRefNo'] ?? null;
        $amount       = isset($payload['pp_Amount']) ? (int) $payload['pp_Amount'] : null;

        // Remove pp_SecureHash from payload before recomputing
        $verifyParams = $payload;
        unset($verifyParams['pp_SecureHash']);

        $computedHash = $this->generateHash($verifyParams);

        $hashValid = hash_equals(strtolower($computedHash), strtolower($receivedHash));
        $isSuccess = $responseCode === '000';

        $verified = $hashValid && $isSuccess;

        $status = match (true) {
            ! $hashValid  => 'hash_mismatch',
            $isSuccess    => 'completed',
            default       => 'failed',
        };

        Log::info('JazzCash: Payment verification', [
            'txn_ref'       => $txnRefNo,
            'response_code' => $responseCode,
            'hash_valid'    => $hashValid,
            'verified'      => $verified,
        ]);

        return [
            'verified'       => $verified,
            'transaction_id' => $txnRefNo,
            'amount_paisas'  => $amount,
            'status'         => $status,
            'response_code'  => $responseCode,
            'raw'            => $payload,
        ];
    }

    /**
     * Initiate a refund for a completed JazzCash payment.
     */
    public function refund(string $transactionId, int $amountPaisas): array
    {
        // JazzCash does not support programmatic refunds via their standard API.
        // Refunds must be processed manually through the JazzCash merchant portal.
        return [
            'success'   => false,
            'refund_id' => null,
            'error'     => 'JazzCash refunds must be processed manually via the merchant portal.',
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty($this->merchantId)
            && ! empty($this->password)
            && ! empty($this->integritySalt);
    }

    /**
     * Generate HMAC-SHA256 secure hash for JazzCash.
     *
     * Algorithm:
     * 1. Filter only pp_* and ppmpf_* params
     * 2. Sort alphabetically by key
     * 3. Concatenate values with & separator
     * 4. Prepend integrity salt with &
     * 5. Hash with HMAC-SHA256
     */
    protected function generateHash(array $params): string
    {
        // Filter only pp_* and ppmpf_* keys
        $hashParams = [];
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'pp_') || str_starts_with($key, 'ppmpf_')) {
                if ($key !== 'pp_SecureHash') {
                    $hashParams[$key] = $value;
                }
            }
        }

        // Sort alphabetically by key
        ksort($hashParams);

        // Build string: salt&value1&value2&...
        $hashString = $this->integritySalt;
        foreach ($hashParams as $value) {
            if ($value !== null && $value !== '') {
                $hashString .= '&' . $value;
            }
        }

        return hash_hmac('sha256', $hashString, $this->integritySalt);
    }
}
