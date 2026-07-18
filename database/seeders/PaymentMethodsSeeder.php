<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Vodafone Cash',
                'code' => 'vodafone_cash',
                'gateway' => 'paymob',
                'is_pos_enabled' => true,
                'is_online_enabled' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Orange Cash',
                'code' => 'orange_cash',
                'gateway' => 'paymob',
                'is_pos_enabled' => true,
                'is_online_enabled' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Etisalat Cash',
                'code' => 'etisalat_cash',
                'gateway' => 'paymob',
                'is_pos_enabled' => true,
                'is_online_enabled' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'InstaPay',
                'code' => 'instapay',
                'gateway' => 'paymob',
                'is_pos_enabled' => true,
                'is_online_enabled' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'We Pay',
                'code' => 'we_pay',
                'gateway' => 'paymob',
                'is_pos_enabled' => true,
                'is_online_enabled' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Cash',
                'code' => 'cash',
                'gateway' => 'cash',
                'is_pos_enabled' => true,
                'is_online_enabled' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'Credit Card',
                'code' => 'credit_card',
                'gateway' => 'paymob',
                'is_pos_enabled' => true,
                'is_online_enabled' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}