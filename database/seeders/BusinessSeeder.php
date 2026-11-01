<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        // Get the test admin user
        $admin = User::where('email', 'admin@test.com')->first();

        if (!$admin) {
            $this->command->error('Admin user not found. Run UserSeeder first.');
            return;
        }

        // Get a plan
        $plan = Plan::first();

        if (!$plan) {
            $this->command->error('Plan not found. Run PlanSeeder first.');
            return;
        }

        // Ensure a business category exists (business_category_id is NOT NULL)
        $category = \App\Models\BusinessCategory::firstOrCreate(
            ['name' => 'Pharmacy'],
            ['status' => true, 'description' => 'Pharmacies and drug stores'],
        );

        // Create test business (columns match create_businesses_table)
        $business = Business::create([
            'companyName' => 'Test Pharmacy',
            'address' => '123 Test Street, Test City',
            'phoneNumber' => '+1234567890',
            'business_category_id' => $category->id,
            'subscriptionDate' => now(),
            'will_expire' => now()->addDays(30),
            'status' => true,
        ]);

        // Attach the admin user to the business
        $admin->update(['business_id' => $business->id]);

        // Create subscription (columns match create_plan_subscribes_table)
        PlanSubscribe::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'price' => $plan->subscriptionPrice,
            'duration' => $plan->duration,
            'payment_status' => 'paid',
            'notes' => 'Seeded test subscription',
        ]);

        $this->command->info('Business seeded successfully');
    }
}
