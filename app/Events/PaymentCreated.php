<?php

namespace App\Events;

use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Created Event
 * 
 * يُرسل عندما يتم إنشاء معاملة دفع جديدة
 */
class PaymentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentTransaction $payment
    ) {}
}