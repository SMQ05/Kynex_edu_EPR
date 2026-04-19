<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Jobs\SendPaymentReceiptNotification;
use App\Models\Tenant\FeePayment;
use App\Models\Tenant\PaymentGatewayLog;
use App\Models\Tenant\StudentFee;
use App\Services\Payment\Drivers\EasyPaisaGateway;
use App\Services\Payment\Drivers\JazzCashGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CallbackController — Handles payment gateway callbacks and return URLs.
 *
 * These routes are public (no auth required) since they are called
 * by the payment gateway servers.
 */
class CallbackController extends Controller
{
    /**
     * POST /payment/jazzcash/callback
     *
     * JazzCash server-to-server callback. Always return HTTP 200.
     */
    public function jazzcashCallback(Request $request): Response
    {
        $payload = $request->all();

        Log::info('JazzCash: Callback received', $payload);

        try {
            $gateway = new JazzCashGateway();
            $result  = $gateway->verifyPayment($payload);

            $txnRefNo     = $result['transaction_id'];
            $responseCd   = $payload['pp_ResponseCode'] ?? '';
            $amountPaisas = $result['amount_paisas'];

            // Find the FeePayment by receipt_number matching pp_TxnRefNo
            $feePayment = FeePayment::where('receipt_number', $txnRefNo)->first();

            if (! $feePayment) {
                Log::warning('JazzCash: FeePayment not found for txn ref', ['txn_ref' => $txnRefNo]);
                $this->logGatewayTransaction('jazzcash', null, null, $txnRefNo, $amountPaisas, 'not_found', $payload);
                return response('OK', 200);
            }

            if ($result['verified']) {
                DB::transaction(function () use ($feePayment, $txnRefNo, $amountPaisas, $payload) {
                    // Update FeePayment status
                    $feePayment->update([
                        'payment_method'  => 'jazzcash',
                        'bank_reference'  => $txnRefNo,
                    ]);

                    // Update related StudentFees to 'paid'
                    $this->markStudentFeesPaid($feePayment);

                    // Create PaymentGatewayLog
                    $this->logGatewayTransaction(
                        'jazzcash',
                        $feePayment->student_id,
                        $feePayment->id,
                        $txnRefNo,
                        $amountPaisas,
                        'completed',
                        $payload
                    );
                });

                // Dispatch receipt notification job
                SendPaymentReceiptNotification::dispatch($feePayment->id);

                Log::info('JazzCash: Payment completed', ['txn_ref' => $txnRefNo, 'fee_payment_id' => $feePayment->id]);
            } else {
                // Payment failed
                $this->logGatewayTransaction(
                    'jazzcash',
                    $feePayment->student_id,
                    $feePayment->id,
                    $txnRefNo,
                    $amountPaisas,
                    'failed',
                    $payload,
                    'Response code: ' . $responseCd . ' | Status: ' . $result['status']
                );

                Log::info('JazzCash: Payment failed', ['txn_ref' => $txnRefNo, 'response_code' => $responseCd]);
            }
        } catch (\Throwable $e) {
            Log::error('JazzCash: Callback processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // JazzCash requires HTTP 200 always
        return response('OK', 200);
    }

    /**
     * POST /payment/easypaisa/callback
     *
     * EasyPaisa server-to-server callback.
     */
    public function easypaisaCallback(Request $request): Response
    {
        $payload = $request->all();

        Log::info('EasyPaisa: Callback received', $payload);

        try {
            $gateway = new EasyPaisaGateway();
            $result  = $gateway->verifyPayment($payload);

            $orderRefNum  = $result['transaction_id'];
            $responseCd   = $payload['responseCode'] ?? '';
            $amountPaisas = $result['amount_paisas'];

            // Find FeePayment by receipt_number
            $feePayment = FeePayment::where('receipt_number', $orderRefNum)->first();

            if (! $feePayment) {
                Log::warning('EasyPaisa: FeePayment not found for order ref', ['order_ref' => $orderRefNum]);
                $this->logGatewayTransaction('easypaisa', null, null, $orderRefNum, $amountPaisas, 'not_found', $payload);
                return response('OK', 200);
            }

            if ($result['verified']) {
                DB::transaction(function () use ($feePayment, $orderRefNum, $amountPaisas, $payload) {
                    $feePayment->update([
                        'payment_method'  => 'easypaisa',
                        'bank_reference'  => $orderRefNum,
                    ]);

                    $this->markStudentFeesPaid($feePayment);

                    $this->logGatewayTransaction(
                        'easypaisa',
                        $feePayment->student_id,
                        $feePayment->id,
                        $orderRefNum,
                        $amountPaisas,
                        'completed',
                        $payload
                    );
                });

                SendPaymentReceiptNotification::dispatch($feePayment->id);

                Log::info('EasyPaisa: Payment completed', ['order_ref' => $orderRefNum, 'fee_payment_id' => $feePayment->id]);
            } else {
                $this->logGatewayTransaction(
                    'easypaisa',
                    $feePayment->student_id,
                    $feePayment->id,
                    $orderRefNum,
                    $amountPaisas,
                    'failed',
                    $payload,
                    'Response code: ' . $responseCd . ' | Status: ' . $result['status']
                );

                Log::info('EasyPaisa: Payment failed', ['order_ref' => $orderRefNum, 'response_code' => $responseCd]);
            }
        } catch (\Throwable $e) {
            Log::error('EasyPaisa: Callback processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response('OK', 200);
    }

    /**
     * GET /payment/jazzcash/return
     *
     * JazzCash browser redirect after payment (user-facing).
     */
    public function jazzcashReturn(Request $request)
    {
        $txnRefNo     = $request->get('pp_TxnRefNo');
        $responseCode = $request->get('pp_ResponseCode');
        $isSuccess    = $responseCode === '000';

        return view('payment.return', [
            'gateway'    => 'JazzCash',
            'success'    => $isSuccess,
            'txn_ref'    => $txnRefNo,
            'message'    => $isSuccess
                ? 'Payment successful! Your fee has been recorded.'
                : 'Payment was not completed. Please try again or contact the school office.',
        ]);
    }

    /**
     * GET /payment/easypaisa/return
     *
     * EasyPaisa browser redirect after payment (user-facing).
     */
    public function easypaisaReturn(Request $request)
    {
        $orderRef     = $request->get('orderRefNum');
        $responseCode = $request->get('responseCode');
        $isSuccess    = $responseCode === '0000';

        return view('payment.return', [
            'gateway'    => 'EasyPaisa',
            'success'    => $isSuccess,
            'txn_ref'    => $orderRef,
            'message'    => $isSuccess
                ? 'Payment successful! Your fee has been recorded.'
                : 'Payment was not completed. Please try again or contact the school office.',
        ]);
    }

    /**
     * Mark related student fees as paid for a given FeePayment.
     */
    protected function markStudentFeesPaid(FeePayment $feePayment): void
    {
        // Get all fee payment items and update their corresponding student fees
        $items = $feePayment->items()->get();

        foreach ($items as $item) {
            $studentFee = StudentFee::find($item->student_fee_id);
            if ($studentFee) {
                $studentFee->update([
                    'paid_paisas' => $studentFee->paid_paisas + $item->amount_paisas,
                    'status'      => ($studentFee->paid_paisas + $item->amount_paisas >= $studentFee->net_payable_paisas)
                        ? 'paid'
                        : 'partial',
                ]);
            }
        }
    }

    /**
     * Log a gateway transaction record.
     */
    protected function logGatewayTransaction(
        string $gateway,
        ?string $studentId,
        ?string $feePaymentId,
        ?string $transactionId,
        ?int $amountPaisas,
        string $status,
        array $payload,
        ?string $errorMessage = null,
    ): void {
        PaymentGatewayLog::create([
            'gateway'           => $gateway,
            'student_id'        => $studentId,
            'fee_payment_id'    => $feePaymentId,
            'transaction_id'    => $transactionId,
            'gateway_reference' => $transactionId,
            'amount_paisas'     => $amountPaisas,
            'status'            => $status,
            'request_payload'   => [],
            'response_payload'  => $payload,
            'error_message'     => $errorMessage,
        ]);
    }
}
