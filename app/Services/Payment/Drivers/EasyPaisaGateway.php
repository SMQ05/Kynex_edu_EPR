<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EasyPaisa Payment Gateway — Full integration with AES-128-ECB encryption.
 *
 * Docs: https://easypay.easypaisa.com.pk/
 */
class EasyPaisaGateway implements PaymentGatewayInterface
{
    protected string $storeId;
    protected string $storePassword;
    protected string $endpoint;
    protected string $statusEndpoint;

    public function __construct()
    {
        $this->storeId       = config('services.easypaisa.store_id', '');
        $this->storePassword = config('services.easypaisa.store_password', '');

        $env = config('services.easypaisa.env', 'sandbox');
        $this->endpoint = $env === 'production'
            ? 'https://easypay.easypaisa.com.pk/easypay/Index.jsf'
            : 'https://easypaystg.easypaisa.com.pk/easypay/Index.jsf';

        $this->statusEndpoint = $env === 'production'
            ? 'https://easypay.easypaisa.com.pk/easypay-service/rest/v4/inquire-transaction'
            : 'https://easypaystg.easypaisa.com.pk/easypay-service/rest/v4/inquire-transaction';
    }

    public function getGatewayName(): string
    {
        return 'easypaisa';
    }

    /**
     * Initiate an EasyPaisa payment.
     *
     * Uses AES-128-ECB encryption for the request payload.
     * Amount is in PKR (decimal, NOT paisas) — we convert from paisas.
     *
     * @param  int     $amountPaisas  Amount in paisas
     * @param  string  $orderId       Unique order/receipt reference
     * @param  array   $meta          Additional metadata
     * @return array{success: bool, redirect_url: ?string, transaction_id: ?string, form_fields: ?array, error: ?string}
     */
    public function initiatePayment(int $amountPaisas, string $orderId, array $meta = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success'        => false,
                'redirect_url'   => null,
                'transaction_id' => null,
                'error'          => 'EasyPaisa gateway not configured. Set credentials in .env.',
            ];
        }

        // Convert paisas to PKR decimal (EasyPaisa expects PKR, not paisas)
        $amountPkr = number_format($amountPaisas / 100, 2, '.', '');

        $txnDateTime       = now()->format('YmdHis');
        $expiryDateTime    = now()->addHour()->format('YmdHis');
        $callbackUrl       = $meta['callback_url'] ?? url('/payment/easypaisa/callback');

        $payload = [
            'storeId'              => $this->storeId,
            'amount'               => $amountPkr,
            'orderRefNum'          => $orderId,
            'transactionDateTime'  => $txnDateTime,
            'expiryDateTime'       => $expiryDateTime,
            'autoRedirect'         => '1',
            'postBackURL'          => $callbackUrl,
            'paymentMethod'        => 'InitialRequest',
            'emailAddress'         => $meta['email'] ?? '',
            'mobileAccountNo'     => $meta['mobile_account'] ?? '',
        ];

        try {
            // Encrypt payload with AES-128-ECB
            $jsonPayload = json_encode($payload);
            $encrypted   = $this->encryptAes($jsonPayload);

            // Build credentials header (Base64 of storeId:storePassword)
            $credentials = base64_encode($this->storeId . ':' . $this->storePassword);

            $response = Http::withHeaders([
                'Credentials' => $credentials,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint, [
                'request' => $encrypted,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('EasyPaisa: Payment initiated', [
                    'order_ref' => $orderId,
                    'amount'    => $amountPkr,
                ]);

                return [
                    'success'        => true,
                    'redirect_url'   => $responseData['url'] ?? $this->endpoint . '?auth_token=' . ($responseData['auth_token'] ?? ''),
                    'transaction_id' => $orderId,
                    'form_fields'    => array_merge($payload, [
                        'auth_token' => $responseData['auth_token'] ?? null,
                    ]),
                    'error'          => null,
                ];
            }

            Log::error('EasyPaisa: Payment initiation failed', [
                'order_ref' => $orderId,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);

            return [
                'success'        => false,
                'redirect_url'   => null,
                'transaction_id' => $orderId,
                'error'          => 'EasyPaisa API returned status: ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('EasyPaisa: Payment initiation exception', [
                'order_ref' => $orderId,
                'error'     => $e->getMessage(),
            ]);

            return [
                'success'        => false,
                'redirect_url'   => null,
                'transaction_id' => $orderId,
                'error'          => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify an EasyPaisa payment via status inquiry.
     *
     * @param  array  $payload  Callback payload or array with 'orderRefNum'
     * @return array{verified: bool, transaction_id: ?string, amount_paisas: ?int, status: string, raw: array}
     */
    public function verifyPayment(array $payload): array
    {
        $orderRefNum  = $payload['orderRefNum'] ?? $payload['order_id'] ?? null;
        $responseCode = $payload['responseCode'] ?? $payload['response_code'] ?? null;

        // If we have a responseCode from callback, check it directly
        if ($responseCode !== null) {
            $isSuccess = $responseCode === '0000';
            $amount    = isset($payload['transactionAmount'])
                ? (int) (floatval($payload['transactionAmount']) * 100)
                : null;

            Log::info('EasyPaisa: Callback verification', [
                'order_ref'     => $orderRefNum,
                'response_code' => $responseCode,
                'verified'      => $isSuccess,
            ]);

            return [
                'verified'       => $isSuccess,
                'transaction_id' => $orderRefNum,
                'amount_paisas'  => $amount,
                'status'         => $isSuccess ? 'completed' : 'failed',
                'response_code'  => $responseCode,
                'raw'            => $payload,
            ];
        }

        // Otherwise, query the status inquiry endpoint
        if (! $orderRefNum) {
            return [
                'verified'       => false,
                'transaction_id' => null,
                'amount_paisas'  => null,
                'status'         => 'invalid',
                'raw'            => $payload,
            ];
        }

        try {
            $credentials = base64_encode($this->storeId . ':' . $this->storePassword);

            $response = Http::withHeaders([
                'Credentials' => $credentials,
                'Content-Type' => 'application/json',
            ])->post($this->statusEndpoint, [
                'orderId'  => $orderRefNum,
                'storeId'  => $this->storeId,
                'accountNum' => '',
            ]);

            if ($response->successful()) {
                $data         = $response->json();
                $statusCode   = $data['responseCode'] ?? '';
                $isSuccess    = $statusCode === '0000';
                $amount       = isset($data['transactionAmount'])
                    ? (int) (floatval($data['transactionAmount']) * 100)
                    : null;

                return [
                    'verified'       => $isSuccess,
                    'transaction_id' => $orderRefNum,
                    'amount_paisas'  => $amount,
                    'status'         => $isSuccess ? 'completed' : 'failed',
                    'response_code'  => $statusCode,
                    'raw'            => $data,
                ];
            }

            return [
                'verified'       => false,
                'transaction_id' => $orderRefNum,
                'amount_paisas'  => null,
                'status'         => 'inquiry_failed',
                'raw'            => ['http_status' => $response->status()],
            ];
        } catch (\Throwable $e) {
            Log::error('EasyPaisa: Status inquiry failed', [
                'order_ref' => $orderRefNum,
                'error'     => $e->getMessage(),
            ]);

            return [
                'verified'       => false,
                'transaction_id' => $orderRefNum,
                'amount_paisas'  => null,
                'status'         => 'error',
                'raw'            => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Initiate a refund for a completed EasyPaisa payment.
     */
    public function refund(string $transactionId, int $amountPaisas): array
    {
        return [
            'success'   => false,
            'refund_id' => null,
            'error'     => 'EasyPaisa refunds must be processed manually via the merchant portal.',
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty($this->storeId)
            && ! empty($this->storePassword);
    }

    /**
     * Encrypt data with AES-128-ECB using the store password as key.
     */
    protected function encryptAes(string $data): string
    {
        $key = substr($this->storePassword, 0, 16);
        $key = str_pad($key, 16, "\0");

        $encrypted = openssl_encrypt(
            $data,
            'AES-128-ECB',
            $key,
            OPENSSL_RAW_DATA
        );

        return base64_encode($encrypted);
    }
}
