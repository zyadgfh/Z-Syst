<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use Illuminate\Database\Seeder;

class ManufacturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'ACI Limited', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ACME Laboratories Ltd.', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Square Ltd.', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'APC Pharma Ltd.', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Beximco', 'business_id' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        Manufacturer::insert($units);
    }
}
