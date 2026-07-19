<?php

namespace App\Listeners;

use App\Events\SubscriptionChanged;

class ResetBranchLimitOnSubscriptionChange
{
    public function handle(SubscriptionChanged $event): void
    {
        $company = $event->company;

        // Check if subscription plan has a branch limit
        if (isset($company->subscriptionPlan->branch_limit)) {
            $company->update([
                'max_branches' => $company->subscriptionPlan->branch_limit,
                'default_branch_limit' => $company->subscriptionPlan->branch_limit,
                'is_unlimited_branches' => false,
                'branch_limit_updated_at' => now(),
                'branch_limit_updated_by' => null,
            ]);
        }
    }
}
