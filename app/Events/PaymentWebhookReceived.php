<?php

namespace App\Events;

use App\Services\Payment\Models\PaymentTransaction;
use App\Services\Payment\DTOs\WebhookPayloadDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Webhook Received Event
 * 
 * يُرسل عند استلام webhook من بوابة الدفع
 */
class PaymentWebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentTransaction $payment,
        public readonly WebhookPayloadDTO $payload
    ) {}
}