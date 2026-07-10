<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BusinessCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business_categories = array(
            array('name' => 'Pharmacy Store', 'description' => 'Retail pharmacy for medicines and healthcare products.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Medical Supply Store', 'description' => 'Store for medical equipment and supplies.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Health & Wellness Store', 'description' => 'Retail store for vitamins, supplements, and health products.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Cosmetic & Beauty Store', 'description' => 'Store for skincare, cosmetics, and beauty products.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Home Care Store', 'description' => 'Store offering home healthcare products and essentials.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Diagnostic Center', 'description' => 'Center for diagnostic and lab tests.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Herbal & Natural Store', 'description' => 'Store for herbal and natural healthcare products.', 'status' => 1, 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Pet Pharmacy', 'description' => 'Store for pet medications and healthcare products.', 'status' => 1, 'created_at' => now(), 'updated_at' => now())
        );

        BusinessCategory::insert($business_categories);
    }
}
