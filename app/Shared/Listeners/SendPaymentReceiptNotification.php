<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send Payment Receipt Notification
 * 
 * إرسال إيصال الدفع عبر الرسائل
 */
class SendPaymentReceiptNotification implements ShouldQueue
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event
     */
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        // Get customer phone
        $customerPhone = $payment->reference->customer?->phone ?? null;
        
        if (!$customerPhone) {
            return;
        }

        // Send receipt via WhatsApp/SMS
        $this->whatsAppService->sendPaymentReceipt(
            phone: $customerPhone,
            amount: $payment->amount,
            currency: $payment->currency,
            transactionId: $payment->external_transaction_id,
            referenceNumber: $payment->external_reference,
            paymentMethod: $payment->payment_method_type,
        );
    }
}