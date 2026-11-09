<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['unit_name' => 'Pack', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'Anacin', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'Pcs', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'Box', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'Strip', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unit_name' => 'Each', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        Unit::insert($units);
    }
}
