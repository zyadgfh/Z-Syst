<?php

namespace Database\Seeders;

use App\Models\PlanSubscribe;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSubscribeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan_subscribes = array(
            array('plan_id' => '1','business_id' => '1','gateway_id' => NULL,'price' => '0','payment_status' => 'unpaid','duration' => '7','notes' => NULL,'created_at' => '2025-01-20 09:52:37','updated_at' => '2025-02-10 09:52:37'),
            array('plan_id' => '1','business_id' => '2','gateway_id' => NULL,'price' => '0','payment_status' => 'unpaid','duration' => '30','notes' => NULL,'created_at' => '2025-01-21 09:53:22','updated_at' => '2025-02-11 09:53:22'),
            array('plan_id' => '1','business_id' => '3','gateway_id' => NULL,'price' => '0','payment_status' => 'unpaid','duration' => '180','notes' => NULL,'created_at' => '2025-01-22 09:53:55','updated_at' => '2025-02-12 09:53:55'),
            array('plan_id' => '1','business_id' => '4','gateway_id' => NULL,'price' => '0','payment_status' => 'unpaid','duration' => '180','notes' => NULL,'created_at' => '2025-01-23 09:53:55','updated_at' => '2025-02-13 09:53:55')
        );

        PlanSubscribe::insert($plan_subscribes);
    }
}
