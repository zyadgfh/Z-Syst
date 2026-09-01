<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\UsageRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function createSubscription(int $businessId, int $planId, array $data = []): Subscription
    {
        return DB::transaction(function () use ($businessId, $planId, $data) {
            $plan = SubscriptionPlan::findOrFail($planId);

            $subscription = Subscription::create([
                'business_id' => $businessId,
                'plan_id' => $planId,
                'status' => $plan->trial_days > 0 ? 'trialing' : 'active',
                'trial_ends_at' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null,
                'starts_at' => now(),
                'ends_at' => $plan->billing_cycle === 'monthly' ? now()->addMonth() : now()->addYear(),
                'payment_method' => $data['payment_method'] ?? null,
                'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
                'stripe_customer_id' => $data['stripe_customer_id'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->logAction($subscription, 'created', null, $subscription->toArray(), $data['performed_by'] ?? null);

            return $subscription;
        });
    }

    public function upgradeSubscription(Subscription $subscription, int $newPlanId, int $performedBy): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlanId, $performedBy) {
            $oldPlan = $subscription->plan;
            $newPlan = SubscriptionPlan::findOrFail($newPlanId);

            $oldData = $subscription->toArray();
            $subscription->update([
                'plan_id' => $newPlanId,
                'ends_at' => $newPlan->billing_cycle === 'monthly' ? now()->addMonth() : now()->addYear(),
            ]);

            $this->logAction($subscription, 'upgraded', $oldData, $subscription->toArray(), $performedBy);

            return $subscription->fresh();
        });
    }

    public function downgradeSubscription(Subscription $subscription, int $newPlanId, int $performedBy): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlanId, $performedBy) {
            $oldPlan = $subscription->plan;
            $newPlan = SubscriptionPlan::findOrFail($newPlanId);

            $oldData = $subscription->toArray();
            $subscription->update([
                'plan_id' => $newPlanId,
                'ends_at' => $newPlan->billing_cycle === 'monthly' ? now()->addMonth() : now()->addYear(),
            ]);

            $this->logAction($subscription, 'downgraded', $oldData, $subscription->toArray(), $performedBy);

            return $subscription->fresh();
        });
    }

    public function cancelSubscription(Subscription $subscription, int $performedBy, ?string $reason = null): Subscription
    {
        return DB::transaction(function () use ($subscription, $performedBy, $reason) {
            $oldData = $subscription->toArray();
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'ends_at' => $subscription->ends_at, // Keep end date for grace period
            ]);

            $this->logAction($subscription, 'cancelled', $oldData, $subscription->toArray(), $performedBy, $reason);

            return $subscription->fresh();
        });
    }

    public function renewSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;

            $oldData = $subscription->toArray();
            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $plan->billing_cycle === 'monthly' ? now()->addMonth() : now()->addYear(),
                'cancelled_at' => null,
            ]);

            $this->logAction($subscription, 'renewed', $oldData, $subscription->toArray(), null);

            return $subscription->fresh();
        });
    }

    public function generateInvoice(Subscription $subscription): SubscriptionInvoice
    {
        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;

            $invoice = SubscriptionInvoice::create([
                'subscription_id' => $subscription->id,
                'business_id' => $subscription->business_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $plan->price,
                'tax' => 0,
                'total' => $plan->price,
                'amount_paid' => 0,
                'status' => 'pending',
            ]);

            return $invoice;
        });
    }

    public function processPayment(SubscriptionInvoice $invoice, array $paymentData): SubscriptionPayment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $payment = SubscriptionPayment::create([
                'invoice_id' => $invoice->id,
                'business_id' => $invoice->business_id,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $invoice->total,
                'currency' => $invoice->subscription->plan->currency,
                'status' => 'processing',
                'payment_method' => $paymentData['payment_method'],
                'gateway' => $paymentData['gateway'] ?? 'local',
                'gateway_transaction_id' => $paymentData['gateway_transaction_id'] ?? null,
                'gateway_response' => $paymentData['gateway_response'] ?? null,
            ]);

            // Simulate payment processing
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $invoice->update([
                'amount_paid' => $invoice->amount_paid + $payment->amount,
                'status' => $invoice->amount_paid >= $invoice->total ? 'paid' : 'pending',
            ]);

            return $payment;
        });
    }

    public function recordUsage(int $businessId, string $metricName, int $quantity): UsageRecord
    {
        return UsageRecord::create([
            'business_id' => $businessId,
            'subscription_id' => Subscription::where('business_id', $businessId)->active()->first()?->id,
            'metric_name' => $metricName,
            'quantity' => $quantity,
            'recorded_at' => now(),
        ]);
    }

    public function getUsage(int $businessId, string $metricName, string $period = 'month'): array
    {
        $subscription = Subscription::where('business_id', $businessId)->active()->first();
        if (! $subscription) {
            return [];
        }

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $records = UsageRecord::where('subscription_id', $subscription->id)
            ->where('metric_name', $metricName)
            ->where('recorded_at', '>=', $startDate)
            ->get();

        return [
            'total' => $records->sum('quantity'),
            'count' => $records->count(),
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => now(),
        ];
    }

    public function checkLimits(int $businessId, string $metricName): bool
    {
        $subscription = Subscription::where('business_id', $businessId)->active()->first();
        if (! $subscription) {
            return false;
        }

        $limits = $subscription->plan->limits ?? [];
        $limit = $limits[$metricName] ?? null;

        if (! $limit) {
            return true;
        } // No limit set

        $usage = $this->getUsage($businessId, $metricName, 'month');

        return $usage['total'] < $limit;
    }

    public function getActiveSubscriptions(): Collection
    {
        return Subscription::active()->with(['plan', 'business'])->get();
    }

    public function getExpiringSubscriptions(int $days = 7): Collection
    {
        return Subscription::active()
            ->where('ends_at', '<=', now()->addDays($days))
            ->where('ends_at', '>', now())
            ->with(['plan', 'business'])
            ->get();
    }

    public function getTrialEndingSubscriptions(int $days = 3): Collection
    {
        return Subscription::trialing()
            ->where('trial_ends_at', '<=', now()->addDays($days))
            ->where('trial_ends_at', '>', now())
            ->with(['plan', 'business'])
            ->get();
    }

    protected function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $lastInvoice = SubscriptionInvoice::where('invoice_number', 'like', "INV-{$date}%")
            ->orderBy('id', 'desc')->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "INV-{$date}-{$newNumber}";
    }

    protected function generatePaymentNumber(): string
    {
        $date = now()->format('Ymd');
        $lastPayment = SubscriptionPayment::where('payment_number', 'like', "PAY-{$date}%")
            ->orderBy('id', 'desc')->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "PAY-{$date}-{$newNumber}";
    }

    protected function logAction(Subscription $subscription, string $action, ?array $oldData, ?array $newData, ?int $performedBy, ?string $notes = null): void
    {
        SubscriptionLog::create([
            'subscription_id' => $subscription->id,
            'business_id' => $subscription->business_id,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'performed_by' => $performedBy,
            'notes' => $notes,
        ]);
    }
}
