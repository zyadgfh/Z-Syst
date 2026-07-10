<?php

namespace Database\Seeders;

use App\Models\Variation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class VariationSeeder extends Seeder
{
    public function run(): void
    {
        $variations = array(
            array('id' => '1', 'business_id' => '1', 'name' => 'Size', 'status' => '1', 'values' => '["Small", "Medium", "Large", "XL"]', 'created_at' => '2024-11-05 09:55:24', 'updated_at' => '2024-11-05 09:55:24'),
            array('id' => '2', 'business_id' => '1', 'name' => 'Color', 'status' => '1', 'values' => '["Red", "Green", "Blue", "Black"]', 'created_at' => '2024-11-05 09:55:24', 'updated_at' => '2024-11-05 09:55:24'),
            array('id' => '3', 'business_id' => '1', 'name' => 'Material', 'status' => '1', 'values' => '["Cotton", "Polyester", "Silk"]', 'created_at' => '2024-11-05 09:55:24', 'updated_at' => '2024-11-05 09:55:24')
        );

        Variation::insert($variations);
    }
}
