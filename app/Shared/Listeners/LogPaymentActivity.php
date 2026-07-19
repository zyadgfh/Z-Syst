<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Events\PaymentSucceeded;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use Illuminate\Support\Facades\Log;

/**
 * Log Payment Activity
 * 
 * تسجيل جميع أنشطة المدفوعات لأغرض التدقيق
 */
class LogPaymentActivity
{
    /**
     * Handle payment created event
     */
    public function handlePaymentCreated(PaymentCreated $event): void
    {
        $payment = $event->payment;

        Log::info('Payment created', [
            'payment_id' => $payment->id,
            'company_id' => $payment->company_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method_type,
            'reference' => $payment->reference_type . ':' . $payment->reference_id,
        ]);
    }

    /**
     * Handle payment succeeded event
     */
    public function handlePaymentSucceeded(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        Log::info('Payment succeeded', [
            'payment_id' => $payment->id,
            'external_transaction_id' => $payment->external_transaction_id,
            'amount' => $payment->amount,
            'completed_at' => $payment->completed_at,
        ]);
    }

    /**
     * Handle payment failed event
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $payment = $event->payment;

        Log::warning('Payment failed', [
            'payment_id' => $payment->id,
            'failure_reason' => $payment->failure_reason,
            'failed_at' => $payment->failed_at,
        ]);
    }

    /**
     * Handle payment refunded event
     */
    public function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $payment = $event->payment;

        Log::info('Payment refunded', [
            'payment_id' => $payment->id,
            'refund_amount' => $payment->refund_amount,
            'refund_reason' => $payment->refund_reason,
            'refunded_at' => $payment->refunded_at,
        ]);
    }
}