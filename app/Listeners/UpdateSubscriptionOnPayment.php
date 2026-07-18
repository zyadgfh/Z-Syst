<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Log;

/**
 * Update Subscription On Payment
 * 
 * تحديث الاشتراك (B2B) عند الدفع الناجح
 */
class UpdateSubscriptionOnPayment
{
    /**
     * Handle the event
     */
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        // Only process subscription payments
        if ($payment->reference_type !== 'subscription' && $payment->reference_type !== 'plan_subscribe') {
            return;
        }

        try {
            $subscription = $payment->reference;
            
            if (!$subscription) {
                Log::warning('Payment succeeded but subscription not found', [
                    'payment_id' => $payment->id,
                    'reference_id' => $payment->reference_id,
                ]);
                return;
            }

            // Extend subscription expiration
            $subscription->update([
                'status' => 'active',
                'expires_at' => $this->calculateExpiryDate($subscription->plan),
                'last_payment_at' => now(),
            ]);

            Log::info('Subscription updated successfully', [
                'subscription_id' => $subscription->id,
                'expires_at' => $subscription->expires_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update subscription', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate expiry date based on plan
     */
    protected function calculateExpiryDate($plan): \DateTime
    {
        $expiresAt = now();
        
        $frequency = $plan->frequency ?? 'monthly';
        
        match($frequency) {
            'daily' => $expiresAt->addDay(),
            'weekly' => $expiresAt->addWeek(),
            'monthly' => $expiresAt->addMonth(),
            'yearly' => $expiresAt->addYear(),
            'lifetime' => $expiresAt->addYears(100), // Very long time
            default => $expiresAt->addMonth(),
        };

        return $expiresAt;
    }
}