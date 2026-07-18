<?php

namespace App\Events;

use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Failed Event
 * 
 * يُرسل عندما يفشل الدفع
 */
class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentTransaction $payment,
        public readonly ?\Throwable $exception = null
    ) {}
}