<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Egyptian payment gateways with multi-tenant support
        // Get the first available currency (or create if needed)
        $currency = Currency::first();
        $currencyId = $currency ? $currency->id : null;
        
        if (!$currencyId) {
            // Create a default USD currency if none exists
            $currencyId = Currency::create([
                'name' => 'US Dollar',
                'country_name' => 'United States',
                'code' => 'USD',
                'rate' => 1,
                'symbol' => '$',
                'position' => 'left',
                'status' => 1,
                'is_default' => 1
            ])->id;
        }

        $gateways = [
            // Vodafone Cash
            ['name' => 'Vodafone Cash', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\VodafoneCash', 'phone_required' => 1, 'instructions' => 'Transfer money to Vodafone Cash number', 'created_at' => now(), 'updated_at' => now()],
            
            // Orange Cash
            ['name' => 'Orange Cash', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\OrangeCash', 'phone_required' => 1, 'instructions' => 'Transfer money to Orange Cash number', 'created_at' => now(), 'updated_at' => now()],
            
            // InstaPay
            ['name' => 'InstaPay', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\InstaPay', 'phone_required' => 1, 'instructions' => 'Transfer money to InstaPay ID', 'created_at' => now(), 'updated_at' => now()],
            
            // Bank Card
            ['name' => 'Bank Card', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\BankCard', 'phone_required' => 0, 'instructions' => 'Transfer money to bank account', 'created_at' => now(), 'updated_at' => now()],
            
            // Fawry
            ['name' => 'Fawry', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\Fawry', 'phone_required' => 0, 'instructions' => 'Pay using Fawry code', 'created_at' => now(), 'updated_at' => now()],
            
            // Cash Payment
            ['name' => 'Cash Payment', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\CashPayment', 'phone_required' => 0, 'instructions' => 'Pay cash at branch', 'created_at' => now(), 'updated_at' => now()],
            
            // Manual payment for supplier payments
            ['name' => 'Manual', 'currency_id' => $currencyId, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '{"label":["Bank Name","Transaction ID"],"is_required":["1","1"]}', 'is_manual' => '1', 'accept_img' => '1', 'namespace' => null, 'phone_required' => 0, 'instructions' => null, 'created_at' => '2024-02-18 17:45:52', 'updated_at' => '2024-02-18 18:24:39'],
        ];

        Gateway::insert($gateways);
    }
}
