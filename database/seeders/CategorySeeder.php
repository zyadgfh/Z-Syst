<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['categoryName' => 'Prescription medicine', 'business_id' => 1, 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['categoryName' => 'Surgical Product', 'business_id' => 1, 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['categoryName' => 'OTC Medicine', 'business_id' => 1, 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['categoryName' => 'Baby Cate', 'business_id' => 1, 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ];

        Category::insert($categories);
    }
}
