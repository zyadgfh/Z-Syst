<?php

namespace Database\Seeders;

use App\Models\PlanSubscribe;
use Illuminate\Database\Seeder;

class PlanSubscribeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan_subscribes = [
            ['plan_id' => '1', 'business_id' => '1', 'gateway_id' => null, 'price' => '0', 'payment_status' => 'unpaid', 'duration' => '7', 'notes' => null, 'created_at' => '2025-01-20 09:52:37', 'updated_at' => '2025-02-10 09:52:37'],
            ['plan_id' => '1', 'business_id' => '2', 'gateway_id' => null, 'price' => '0', 'payment_status' => 'unpaid', 'duration' => '30', 'notes' => null, 'created_at' => '2025-01-21 09:53:22', 'updated_at' => '2025-02-11 09:53:22'],
            ['plan_id' => '1', 'business_id' => '3', 'gateway_id' => null, 'price' => '0', 'payment_status' => 'unpaid', 'duration' => '180', 'notes' => null, 'created_at' => '2025-01-22 09:53:55', 'updated_at' => '2025-02-12 09:53:55'],
            ['plan_id' => '1', 'business_id' => '4', 'gateway_id' => null, 'price' => '0', 'payment_status' => 'unpaid', 'duration' => '180', 'notes' => null, 'created_at' => '2025-01-23 09:53:55', 'updated_at' => '2025-02-13 09:53:55'],
        ];

        PlanSubscribe::insert($plan_subscribes);
    }
}
