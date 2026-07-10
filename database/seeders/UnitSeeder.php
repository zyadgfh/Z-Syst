<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = array(
            array('unitName' => 'Pack','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('unitName' => 'Anacin','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('unitName' => 'Pcs','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('unitName' => 'Box','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('unitName' => 'Strip','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
            array('unitName' => 'Each','business_id' => 1,'status' => 1,'created_at' => now(),'updated_at' => now()),
        );

        Unit::insert($units);
    }
}
