<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\SubscriptionUpgrade;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;

class PlanManagementService
{
    use WithTransactionalOperations;

    /**
     * Get available plans for upgrade.
     *
     * @param int $businessId
     * @return Collection
     */
    public function getAvailablePlans(int $businessId): Collection
    {
        $currentSubscription = Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->first();

        $currentPlanId = $currentSubscription ? $currentSubscription->plan_id : null;

        return Plan::where('is_active', true)
            ->where('id', '!=', $currentPlanId)
            ->with('features')
            ->get()
            ->map(function ($plan) use ($currentPlanId) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'billing_cycle' => $plan->billing_cycle,
                    'features' => $plan->features->pluck('name'),
                    'is_upgrade' => $this->isUpgrade($currentPlanId, $plan->id),
                ];
            });
    }

    /**
     * Upgrade subscription to a new plan.
     *
     * @param int $businessId
     * @param int $newPlanId
     * @param bool $prorated
     * @return array
     */
    public function upgradePlan(int $businessId, int $newPlanId, bool $prorated = true): array
    {
        return $this->executeTransaction(function () use ($businessId, $newPlanId, $prorated) {
            $currentSubscription = Subscription::where('business_id', $businessId)
                ->where('status', 'active')
                ->firstOrFail();

            $newPlan = Plan::findOrFail($newPlanId);

            // Calculate prorated amount if requested
            $amount = $prorated 
                ? $this->calculateProratedAmount($currentSubscription, $newPlan)
                : $newPlan->price;

            // Create upgrade record
            $upgrade = SubscriptionUpgrade::create([
                'business_id' => $businessId,
                'from_plan_id' => $currentSubscription->plan_id,
                'to_plan_id' => $newPlanId,
                'amount' => $amount,
                'prorated' => $prorated,
                'status' => 'pending',
                'effective_date' => now(),
            ]);

            // Update subscription
            $currentSubscription->update([
                'plan_id' => $newPlanId,
                'amount' => $amount,
                'upgraded_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Plan upgraded successfully',
                'upgrade_id' => $upgrade->id,
                'new_plan' => $newPlan->name,
                'amount' => $amount,
            ];
        });
    }

    /**
     * Downgrade subscription to a lower plan.
     *
     * @param int $businessId
     * @param int $newPlanId
     * @param Carbon|null $effectiveDate
     * @return array
     */
    public function downgradePlan(int $businessId, int $newPlanId, ?Carbon $effectiveDate = null): array
    {
        return $this->executeTransaction(function () use ($businessId, $newPlanId, $effectiveDate) {
            $currentSubscription = Subscription::where('business_id', $businessId)
                ->where('status', 'active')
                ->firstOrFail();

            $newPlan = Plan::findOrFail($newPlanId);

            $effectiveDate = $effectiveDate ?? now()->addMonth();

            // Create downgrade record
            $upgrade = SubscriptionUpgrade::create([
                'business_id' => $businessId,
                'from_plan_id' => $currentSubscription->plan_id,
                'to_plan_id' => $newPlanId,
                'amount' => $newPlan->price,
                'is_downgrade' => true,
                'status' => 'pending',
                'effective_date' => $effectiveDate,
            ]);

            // Schedule the downgrade
            $currentSubscription->update([
                'downgrade_to_plan_id' => $newPlanId,
                'downgrade_effective_date' => $effectiveDate,
            ]);

            return [
                'success' => true,
                'message' => 'Plan downgrade scheduled',
                'effective_date' => $effectiveDate->toDateString(),
                'new_plan' => $newPlan->name,
            ];
        });
    }

    /**
     * Cancel subscription.
     *
     * @param int $businessId
     * @param string $reason
     * @param Carbon|null $effectiveDate
     * @return array
     */
    public function cancelSubscription(int $businessId, string $reason, ?Carbon $effectiveDate = null): array
    {
        return $this->executeTransaction(function () use ($businessId, $reason, $effectiveDate) {
            $subscription = Subscription::where('business_id', $businessId)
                ->where('status', 'active')
                ->firstOrFail();

            $effectiveDate = $effectiveDate ?? now()->addMonth();

            $subscription->update([
                'status' => 'cancelled',
                'cancel_reason' => $reason,
                'cancel_effective_date' => $effectiveDate,
                'cancelled_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Subscription cancelled',
                'effective_date' => $effectiveDate->toDateString(),
            ];
        });
    }

    /**
     * Resume cancelled subscription.
     *
     * @param int $businessId
     * @return array
     */
    public function resumeSubscription(int $businessId): array
    {
        return $this->executeTransaction(function () use ($businessId) {
            $subscription = Subscription::where('business_id', $businessId)
                ->where('status', 'cancelled')
                ->firstOrFail();

            $subscription->update([
                'status' => 'active',
                'cancel_reason' => null,
                'cancel_effective_date' => null,
                'cancelled_at' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Subscription resumed',
            ];
        });
    }

    /**
     * Check if a plan is an upgrade from current plan.
     *
     * @param int|null $currentPlanId
     * @param int $newPlanId
     * @return bool
     */
    protected function isUpgrade(?int $currentPlanId, int $newPlanId): bool
    {
        if (!$currentPlanId) {
            return true;
        }

        $currentPlan = Plan::find($currentPlanId);
        $newPlan = Plan::find($newPlanId);

        return $newPlan->price > $currentPlan->price;
    }

    /**
     * Calculate prorated amount for plan upgrade.
     *
     * @param Subscription $currentSubscription
     * @param Plan $newPlan
     * @return float
     */
    protected function calculateProratedAmount(Subscription $currentSubscription, Plan $newPlan): float
    {
        $currentPlan = $currentSubscription->plan;
        
        $remainingDays = $currentSubscription->ends_at->diffInDays(now());
        $totalDays = $currentSubscription->starts_at->diffInDays($currentSubscription->ends_at);
        
        $daysUsed = $totalDays - $remainingDays;
        $proratedRatio = $remainingDays / $totalDays;
        
        $priceDifference = $newPlan->price - $currentPlan->price;
        
        return $currentPlan->amount + ($priceDifference * $proratedRatio);
    }

    /**
     * Get plan comparison.
     *
     * @param int $currentPlanId
     * @param int $newPlanId
     * @return array
     */
    public function comparePlans(int $currentPlanId, int $newPlanId): array
    {
        $currentPlan = Plan::with('features')->findOrFail($currentPlanId);
        $newPlan = Plan::with('features')->findOrFail($newPlanId);

        $currentFeatures = $currentPlan->features->pluck('name')->toArray();
        $newFeatures = $newPlan->features->pluck('name')->toArray();

        $addedFeatures = array_diff($newFeatures, $currentFeatures);
        $removedFeatures = array_diff($currentFeatures, $newFeatures);

        return [
            'current_plan' => [
                'name' => $currentPlan->name,
                'price' => $currentPlan->price,
                'features' => $currentFeatures,
            ],
            'new_plan' => [
                'name' => $newPlan->name,
                'price' => $newPlan->price,
                'features' => $newFeatures,
            ],
            'price_difference' => $newPlan->price - $currentPlan->price,
            'added_features' => $addedFeatures,
            'removed_features' => $removedFeatures,
            'is_upgrade' => $newPlan->price > $currentPlan->price,
        ];
    }
}