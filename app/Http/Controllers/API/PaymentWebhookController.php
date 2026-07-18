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
     * Handle webhook callback from payment gateway
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        $payload = $request->all();

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
     * Handle Paymob webhook specifically
     */
    public function handlePaymob(Request $request): JsonResponse
    {
        $payload = $request->all();
        $hmac = $request->header('X-HMAC-Signature');

        Log::info('Paymob webhook received', [
            'hmac' => $hmac,
            'payload' => $payload,
        ]);

        // Dispatch to queue
        ProcessPaymentWebhook::dispatch('paymob', $payload)
            ->onQueue('payments-webhooks');

        return response()->json(['status' => 'received'], 200);
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