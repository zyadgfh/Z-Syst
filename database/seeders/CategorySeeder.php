<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = array(
            array('categoryName' => 'Prescription medicine','business_id' => 1,'status' => 0,'created_at' => now(),'updated_at' => now()),
            array('categoryName' => 'Surgical Product','business_id' => 1,'status' => 0,'created_at' => now(),'updated_at' => now()),
            array('categoryName' => 'OTC Medicine','business_id' => 1,'status' => 0,'created_at' => now(),'updated_at' => now()),
            array('categoryName' => 'Baby Cate','business_id' => 1,'status' => 0,'created_at' => now(),'updated_at' => now()),
        );

        Category::insert($categories);
    }
}
