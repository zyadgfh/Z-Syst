<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessPaymentWebhook;
use App\Services\Payment\Services\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Payment Webhook Controller
 *
 * معالجة webhooks من بوابات الدفع
 */
class PaymentWebhookController
{
    public function __construct(
        protected PaymentGatewayFactory $gatewayFactory
    ) {}

    /**
     * Handle Paymob webhook specifically
     */
    public function handlePaymob(Request $request): JsonResponse
    {
        $payload = $request->all();
        $hmacHeader = $request->header('X-HMAC-Signature');

        // Verify HMAC signature
        $secret = config('services.paymob.hmac_secret', env('PAYMOB_HMAC_SECRET'));
        if ($secret && $hmacHeader) {
            $calculatedHmac = hash_hmac('sha512', json_encode($payload), $secret);
            if (!hash_equals($calculatedHmac, $hmacHeader)) {
                Log::warning('Paymob webhook HMAC verification failed', [
                    'received_hmac' => $hmacHeader,
                    'calculated_hmac' => $calculatedHmac,
                ]);
                return response()->json(['status' => 'invalid_signature'], 401);
            }
        } elseif ($secret) {
            Log::warning('Paymob webhook missing HMAC header');
            return response()->json(['status' => 'missing_signature'], 401);
        }

        Log::info('Paymob webhook received', [
            'hmac' => $hmacHeader,
            'payload' => $payload,
        ]);

        // Dispatch to queue
        ProcessPaymentWebhook::dispatch('paymob', $payload)
            ->onQueue('payments-webhooks');

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Generic webhook handler with HMAC verification for all gateways
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        $payload = $request->all();

        // Verify gateway-specific signature
        $secret = config("services.{$gateway}.webhook_secret", env(strtoupper($gateway) . '_WEBHOOK_SECRET'));
        $signature = $request->header('X-Signature') ?? $request->header('X-Webhook-Signature') ?? $request->input('signature');

        if ($secret && $signature) {
            $calculated = hash_hmac('sha256', json_encode($payload), $secret);
            if (!hash_equals($calculated, $signature)) {
                Log::warning("{$gateway} webhook signature verification failed");
                return response()->json(['status' => 'invalid_signature'], 401);
            }
        } elseif ($secret) {
            Log::warning("{$gateway} webhook missing signature header");
            return response()->json(['status' => 'missing_signature'], 401);
        }

        // Log the raw webhook (for debugging and audit)
        $this->logWebhook($gateway, $request, $payload);

        // Dispatch to queue for processing (don't block the gateway)
        ProcessPaymentWebhook::dispatch($gateway, $payload)
            ->onQueue('payments-webhooks')
            ->delay(now()->addSeconds(5)); // Small delay to ensure order

        // Return 200 immediately (gateways expect quick response)
        return response()->json([
            'status' => 'received',
            'message' => 'Webhook is being processed',
        ], 200);
    }

    /**
     * Log webhook for audit
     */
    protected function logWebhook(string $gateway, Request $request, array $payload): void
    {
        try {
            \App\Models\PaymentGatewayLog::create([
                'gateway' => $gateway,
                'endpoint' => $request->fullUrl(),
                'method' => $request->method(),
                'request_headers' => $request->headers->all(),
                'request_body' => $payload,
                'response_status' => 200,
                'success' => true,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log webhook', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
