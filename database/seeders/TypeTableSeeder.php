<?php

namespace Database\Seeders;

use App\Models\MedicineType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vats = array(
            array('name' => 'Capsule', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Injection', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Syrup', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Ampoule', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Bottle', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
        );

        MedicineType::insert($vats);
    }
}
