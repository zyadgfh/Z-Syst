<?php

namespace Database\Seeders;

use App\Models\BoxSize;
use Illuminate\Database\Seeder;

class BoxSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => '1 Box 10 Strip', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Stripe of 20 (20)', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maxbit (122)', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Box (3)', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Strip (24)', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Stripe (100)', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        BoxSize::insert($units);
    }
}
