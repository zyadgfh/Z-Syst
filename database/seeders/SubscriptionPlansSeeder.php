<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Free plan for testing and evaluation',
                'price' => 0,
                'currency' => 'EGP',
                'duration_days' => 30,
                'rate_limit' => 100,
                'max_branches' => 1,
                'max_products' => 100,
                'max_users' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small pharmacies',
                'price' => 499,
                'currency' => 'EGP',
                'duration_days' => 30,
                'rate_limit' => 1000,
                'max_branches' => 3,
                'max_products' => 1000,
                'max_users' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing pharmacy chains',
                'price' => 1499,
                'currency' => 'EGP',
                'duration_days' => 30,
                'rate_limit' => 10000,
                'max_branches' => 10,
                'max_products' => 10000,
                'max_users' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited features for large organizations',
                'price' => 4999,
                'currency' => 'EGP',
                'duration_days' => 30,
                'rate_limit' => 100000,
                'max_branches' => 999,
                'max_products' => 999999,
                'max_users' => 999,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}