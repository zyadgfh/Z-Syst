<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Handle Vodafone Cash webhook.
     */
    public function vodafoneCash(Request $request)
    {
        try {
            $signature = $request->header('X-Vodafone-Signature');
            $payload = $request->getContent();

            // Verify webhook signature
            if (! $this->verifyVodafoneSignature($payload, $signature)) {
                Log::warning('Invalid Vodafone Cash webhook signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);
            $this->processWebhookData('vodafone_cash', $data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Vodafone Cash webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Bank Card webhook.
     */
    public function bankCard(Request $request)
    {
        try {
            $signature = $request->header('X-Payment-Signature');
            $payload = $request->getContent();

            // Verify webhook signature
            if (! $this->verifyBankCardSignature($payload, $signature)) {
                Log::warning('Invalid Bank Card webhook signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);
            $this->processWebhookData('bank_card', $data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Bank Card webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Fawry webhook.
     */
    public function fawry(Request $request)
    {
        try {
            $signature = $request->header('X-Fawry-Signature');
            $payload = $request->getContent();

            // Verify webhook signature
            if (! $this->verifyFawrySignature($payload, $signature)) {
                Log::warning('Invalid Fawry webhook signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);
            $this->processWebhookData('fawry', $data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Fawry webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Orange Cash webhook.
     */
    public function orangeCash(Request $request)
    {
        try {
            $signature = $request->header('X-Orange-Signature');
            $payload = $request->getContent();

            // Verify webhook signature
            if (! $this->verifyOrangeSignature($payload, $signature)) {
                Log::warning('Invalid Orange Cash webhook signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);
            $this->processWebhookData('orange_cash', $data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Orange Cash webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle InstaPay webhook.
     */
    public function instaPay(Request $request)
    {
        try {
            $signature = $request->header('X-Instapay-Signature');
            $payload = $request->getContent();

            // Verify webhook signature
            if (! $this->verifyInstaPaySignature($payload, $signature)) {
                Log::warning('Invalid InstaPay webhook signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);
            $this->processWebhookData('instapay', $data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('InstaPay webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process webhook data and update transaction status.
     */
    protected function processWebhookData(string $gatewayType, array $data): void
    {
        $referenceId = $data['reference_id'] ?? $data['merchant_ref_num'] ?? $data['transaction_id'] ?? null;

        if (! $referenceId) {
            Log::error('Webhook data missing reference ID', ['gateway' => $gatewayType, 'data' => $data]);

            return;
        }

        $transaction = PaymentTransaction::where('reference_id', $referenceId)
            ->where('gateway_type', $gatewayType)
            ->first();

        if (! $transaction) {
            Log::warning('Transaction not found for webhook', ['reference_id' => $referenceId, 'gateway' => $gatewayType]);

            return;
        }

        // Determine payment status from webhook data
        $status = $this->determineWebhookStatus($data);

        switch ($status) {
            case 'success':
            case 'completed':
            case 'paid':
                if ($transaction->status !== PaymentTransaction::STATUS_COMPLETED) {
                    $transaction->markAsCompleted($referenceId, $data);
                    Log::info('Transaction completed via webhook', ['transaction_id' => $transaction->id]);
                }
                break;

            case 'failed':
            case 'rejected':
            case 'cancelled':
                if ($transaction->status !== PaymentTransaction::STATUS_FAILED) {
                    $transaction->markAsFailed($data['failure_reason'] ?? 'Payment failed via webhook');
                    Log::info('Transaction failed via webhook', ['transaction_id' => $transaction->id]);
                }
                break;

            case 'pending':
            case 'processing':
                // Keep transaction in pending state
                $transaction->metadata = array_merge($transaction->metadata ?? [], [
                    'webhook_status' => $status,
                    'webhook_data' => $data,
                ]);
                $transaction->save();
                break;
        }
    }

    /**
     * Determine payment status from webhook data.
     */
    protected function determineWebhookStatus(array $data): string
    {
        $status = $data['status'] ?? $data['payment_status'] ?? $data['state'] ?? null;

        return match (strtolower($status)) {
            'success', 'completed', 'paid', 'captured' => 'success',
            'failed', 'rejected', 'cancelled', 'declined' => 'failed',
            'pending', 'processing', 'awaiting' => 'pending',
            default => 'unknown',
        };
    }

    /**
     * Verify Vodafone Cash webhook signature.
     */
    protected function verifyVodafoneSignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment.vodafone_cash_api_secret');
        if (! $secret || ! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Bank Card webhook signature.
     */
    protected function verifyBankCardSignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment.bank_card_api_secret');
        if (! $secret || ! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Fawry webhook signature.
     */
    protected function verifyFawrySignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment.fawry_security_key');
        if (! $secret || ! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Orange Cash webhook signature.
     */
    protected function verifyOrangeSignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment.orange_cash_api_secret');
        if (! $secret || ! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify InstaPay webhook signature.
     */
    protected function verifyInstaPaySignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment.instapay_api_secret');
        if (! $secret || ! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
