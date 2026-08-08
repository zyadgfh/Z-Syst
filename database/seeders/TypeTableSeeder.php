<?php

namespace Database\Seeders;

use App\Models\MedicineType;
use Illuminate\Database\Seeder;

class TypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vats = [
            ['name' => 'Capsule', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Injection', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Syrup', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ampoule', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bottle', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        MedicineType::insert($vats);
    }
}
