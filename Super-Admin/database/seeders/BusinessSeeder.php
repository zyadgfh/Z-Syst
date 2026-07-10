<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = array(
            array('plan_subscribe_id' => '1', 'business_category_id' => '1', 'companyName' => 'Trade G', 'will_expire' => '2035-12-15', 'address' => 'Dhaka, Bangladesh', 'email' => 'tradeground@gmail.com', 'phoneNumber' => '01712022529', 'pictureUrl' => NULL, 'subscriptionDate' => '2025-12-15 00:00:00', 'remainingShopBalance' => '0.00', 'shopOpeningBalance' => '100.00', 'vat_no' => '1234', 'vat_name' => 'GST11', 'meta' => '{"show_company_name":1,"show_phone_number":1,"show_address":1,"show_email":1,"show_vat":1}', 'status' => '1', 'created_at' => '2025-12-15 14:00:38', 'updated_at' => '2025-12-15 14:01:16')
        );

        Business::insert($businesses);
    }
}
