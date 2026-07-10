<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vats = array(
            array('name' => 'Gov. Tax', 'business_id' => 1, 'rate' => 5, 'sub_tax' => NULL, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'GST', 'business_id' => 1, 'rate' => 5, 'sub_tax' => NULL, 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Group Tax', 'business_id' => 1, 'rate' => 10, 'sub_tax' => 'Gov. Tax + GST', 'status' => 1, 'created_at' => now(), 'updated_at' => now())
        );

        Tax::insert($vats);
    }
}
