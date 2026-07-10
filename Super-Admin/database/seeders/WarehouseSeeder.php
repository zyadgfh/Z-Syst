<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = array(
            array('business_id' => '1', 'name' => 'Asira Storage', 'phone' => '+1 (997) 278-8846', 'email' => 'hovurubig@mailinator.com', 'address' => 'Porro dolor saepe fu', 'created_at' => '2025-07-02 06:25:54', 'updated_at' => '2025-07-02 06:25:54'),
            array('business_id' => '1', 'name' => 'Asia Stock', 'phone' => '+1 (658) 795-2164', 'email' => 'desyzime@mailinator.com', 'address' => 'Ipsa eos dolore sit', 'created_at' => '2025-07-02 06:26:38', 'updated_at' => '2025-07-02 06:26:38'),
            array('business_id' => '1', 'name' => 'Pacific Vault', 'phone' => '+1 (162) 411-8463', 'email' => 'xynonyja@mailinator.com', 'address' => 'Sit optio consequa', 'created_at' => '2025-07-02 06:26:53', 'updated_at' => '2025-07-02 06:26:53'),
            array('business_id' => '1', 'name' => 'Lotus Depot', 'phone' => '+1 (684) 462-6825', 'email' => 'huquba@mailinator.com', 'address' => 'Eum pariatur Pariat', 'created_at' => '2025-07-02 06:27:05', 'updated_at' => '2025-07-02 06:27:05')
        );

        Warehouse::insert($warehouses);
    }
}
