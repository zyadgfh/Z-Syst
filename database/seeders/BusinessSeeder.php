<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = array(
            array('plan_subscribe_id' => '1','business_category_id' => '1','companyName' => 'Z-Syst Team','will_expire' => now()->addDays(5000),'address' => 'Dhaka','phoneNumber' => '+8801712022529','pictureUrl' => NULL,'subscriptionDate' => '2024-10-27 11:21:21','remainingShopBalance' => 10000,'shopOpeningBalance' => 10000,'created_at' => '2024-10-27 11:21:20','updated_at' => '2024-10-27 11:21:21')
        );

        Business::insert($businesses);
    }
}
