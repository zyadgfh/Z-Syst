<?php

namespace App\Events;

use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Succeeded Event
 * 
 * يُرسل عندما يتم الدفع بنجاح
 */
class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentTransaction $payment
    ) {}
}