<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', function () {
                return [
                    'id' => $this->business->id,
                    'name' => $this->business->name,
                ];
            }),
            'plan_id' => $this->plan_id,
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan->id,
                    'name' => $this->plan->name,
                    'slug' => $this->plan->slug,
                    'price' => $this->plan->price,
                    'currency' => $this->plan->currency,
                    'billing_cycle' => $this->plan->billing_cycle,
                    'features' => $this->plan->features,
                    'limits' => $this->plan->limits,
                ];
            }),
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at?->format('Y-m-d H:i:s'),
            'starts_at' => $this->starts_at->format('Y-m-d H:i:s'),
            'ends_at' => $this->ends_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'payment_method' => $this->payment_method,
            'stripe_subscription_id' => $this->stripe_subscription_id,
            'stripe_customer_id' => $this->stripe_customer_id,
            'metadata' => $this->metadata,
            'is_trial' => $this->isTrial(),
            'is_active' => $this->isActive(),
            'is_cancelled' => $this->isCancelled(),
            'on_trial' => $this->onTrial(),
            'has_grace_period' => $this->hasGracePeriod(),
            'days_until_renewal' => $this->daysUntilRenewal(),
            'days_in_trial' => $this->daysInTrial(),
            'invoices_count' => $this->whenLoaded('invoices', fn () => $this->invoices->count()),
            'usage_records_count' => $this->whenLoaded('usageRecords', fn () => $this->usageRecords->count()),
            'logs_count' => $this->whenLoaded('logs', fn () => $this->logs->count()),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
