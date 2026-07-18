<?php

namespace App\Jobs;

use App\Services\Payment\Models\PaymentTransaction;
use App\Services\Payment\Services\PaymentGatewayFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Verify Payment Status Job
 * 
 * التحقق من حالة الدفع وإعادة المحاولة إذا لزم الأمر
 */
class VerifyPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly PaymentTransaction $transaction
    ) {}

    public function handle(PaymentGatewayFactory $factory): void
    {
        $gateway = $factory->getByMethod($this->transaction->payment_method_type);
        
        $updated = $gateway->verify($this->transaction);

        Log::info('Payment status verified', [
            'transaction_id' => $this->transaction->id,
            'status' => $updated->status,
        ]);
    }
}