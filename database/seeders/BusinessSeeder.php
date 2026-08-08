<?php

namespace Database\Seeders;

use App\Models\Business;
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
        $plan = \App\Models\Plan::first();
        
        if (!$plan) {
            $this->command->error('Plan not found. Run PlanSeeder first.');
            return;
        }

        // Create test business
        $business = Business::create([
            'businessName' => 'Test Pharmacy',
            'businessPhone' => '+1234567890',
            'businessEmail' => 'info@testpharmacy.com',
            'businessAddress' => '123 Test Street, Test City',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'zipCode' => '12345',
            'status' => 'active',
            'will_expire' => now()->addDays(30),
        ]);

        // Update admin user with business
        $admin->update(['business_id' => $business->id]);

        // Create subscription
        PlanSubscribe::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'price' => $plan->subscriptionPrice,
            'duration' => $plan->duration,
            'start_date' => now(),
            'end_date' => now()->addDays($plan->duration),
            'status' => 'active',
        ]);

        $this->command->info('Business seeded successfully');
    }
}
