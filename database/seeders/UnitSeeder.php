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
            ['unitName' => 'Pack', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unitName' => 'Anacin', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unitName' => 'Pcs', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unitName' => 'Box', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unitName' => 'Strip', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['unitName' => 'Each', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        Unit::insert($units);
    }
}
