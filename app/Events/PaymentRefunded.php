<?php

namespace App\Events;

use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Refunded Event
 * 
 * يُرسل عندما يتم استرداد مبلغ الدفع
 */
class PaymentRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentTransaction $payment,
        public readonly ?object $refund = null
    ) {}
}