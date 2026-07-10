<?php

namespace Database\Seeders;

use App\Models\Shelf;
use Illuminate\Database\Seeder;

class ShelfSeeder extends Seeder
{
    public function run(): void
    {
        $shelves = array(
            array('business_id' => '1','name' => 'A','status' => '1','created_at' => now(),'updated_at' => now()),
            array('business_id' => '1','name' => 'B','status' => '1','created_at' => now(),'updated_at' => now()),
            array('business_id' => '1','name' => 'C','status' => '1','created_at' => now(),'updated_at' => now()),
            array('business_id' => '1','name' => 'D','status' => '1','created_at' => now(),'updated_at' => now())
        );

        Shelf::insert($shelves);
    }
}
