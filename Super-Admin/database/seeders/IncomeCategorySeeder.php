<?php

namespace Database\Seeders;

use App\Models\IncomeCategory;
use Illuminate\Database\Seeder;

class IncomeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $income_categories = array(
            array('categoryName' => 'Sales', 'business_id' => '1', 'categoryDescription' => 'Product sales income', 'status' => '1', 'created_at' => '2025-08-01 10:00:00', 'updated_at' => '2025-08-10 12:00:00'),
            array('categoryName' => 'Service', 'business_id' => '1', 'categoryDescription' => 'Income from services', 'status' => '1', 'created_at' => '2025-08-02 11:15:00', 'updated_at' => '2025-08-11 13:20:00'),
            array('categoryName' => 'Rental', 'business_id' => '1', 'categoryDescription' => 'Rental income', 'status' => '1', 'created_at' => '2025-08-03 09:30:00', 'updated_at' => '2025-08-12 14:10:00'),
            array('categoryName' => 'Commission', 'business_id' => '1', 'categoryDescription' => 'Commission earned', 'status' => '1', 'created_at' => '2025-08-04 08:45:00', 'updated_at' => '2025-08-13 15:05:00'),
            array('categoryName' => 'Interest', 'business_id' => '1', 'categoryDescription' => 'Interest income', 'status' => '1', 'created_at' => '2025-08-05 10:20:00', 'updated_at' => '2025-08-14 16:25:00'),
            array('categoryName' => 'Refund', 'business_id' => '1', 'categoryDescription' => 'Refund from suppliers', 'status' => '1', 'created_at' => '2025-08-06 11:55:00', 'updated_at' => '2025-08-15 17:40:00'),
            array('categoryName' => 'Other', 'business_id' => '1', 'categoryDescription' => 'Other miscellaneous income', 'status' => '1', 'created_at' => '2025-08-07 12:10:00', 'updated_at' => '2025-08-16 18:00:00')
        );

        IncomeCategory::insert($income_categories);
    }
}
