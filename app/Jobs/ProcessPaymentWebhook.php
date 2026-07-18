<?php

namespace App\Jobs;

use App\Services\Payment\Services\PaymentGatewayFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process Payment Webhook Job
 * 
 * معالجة webhooks في الخلفية لتفادي حجب البوابة
 */
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 60, 300, 900]; // 30s, 1m, 5m, 15m
    public int $maxExceptions = 3;

    /**
     * Create a new job instance
     */
    public function __construct(
        public readonly string $gateway,
        public readonly array $payload
    ) {}

    /**
     * Execute the job
     */
    public function handle(PaymentGatewayFactory $factory): void
    {
        $gateway = $factory->make($this->gateway);

        // Verify webhook signature if needed
        $hmac = null;
        if ($this->gateway === 'paymob') {
            $hmac = request()->header('X-HMAC-Signature');
            // Signature verification is done inside PaymobGateway
        }

        $transaction = $gateway->handleWebhook($this->payload);

        if ($transaction) {
            Log::info('Payment webhook processed', [
                'gateway' => $this->gateway,
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('Payment webhook processing failed', [
            'gateway' => $this->gateway,
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify admin team via Slack/email
        // TODO: Implement notification
    }
}