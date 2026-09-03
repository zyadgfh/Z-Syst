<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Keeping only manual payment option for supplier payments
        // All external payment gateways have been removed
        $gateways = [
            ['name' => 'Manual', 'currency_id' => 4, 'mode' => 1, 'status' => '1', 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '{"label":["Bank Name","Transaction ID"],"is_required":["1","1"]}', 'is_manual' => '1', 'accept_img' => '1', 'namespace' => null, 'phone_required' => 0, 'instructions' => null, 'created_at' => '2024-02-18 17:45:52', 'updated_at' => '2024-02-18 18:24:39'],
        ];

        Gateway::insert($gateways);
    }
}
