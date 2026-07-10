<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\Manufacturer;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ManufacturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = array(
            array('name' => 'ACI Limited','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('name' => 'ACME Laboratories Ltd.','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('name' => 'Square Ltd.','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('name' => 'APC Pharma Ltd.','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('name' => 'Beximco','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
        );

        Manufacturer::insert($units);
    }
}
