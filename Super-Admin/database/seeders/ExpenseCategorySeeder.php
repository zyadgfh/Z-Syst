<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $expense_categories = array(
            array('categoryName' => 'Others', 'business_id' => '1', 'categoryDescription' => 'Expenses on purchasing goods', 'status' => '1', 'created_at' => '2025-08-13 11:51:42', 'updated_at' => '2025-08-13 11:51:42'),
            array('categoryName' => 'Utilities', 'business_id' => '1', 'categoryDescription' => 'Electricity, water, and gas bills', 'status' => '1', 'created_at' => '2025-08-10 09:15:00', 'updated_at' => '2025-08-10 09:15:00'),
            array('categoryName' => 'Salary', 'business_id' => '1', 'categoryDescription' => 'Employee salary payments', 'status' => '1', 'created_at' => '2025-08-11 10:30:00', 'updated_at' => '2025-08-11 10:30:00'),
            array('categoryName' => 'Maintenance', 'business_id' => '1', 'categoryDescription' => 'Equipment and building maintenance', 'status' => '1', 'created_at' => '2025-08-12 14:45:00', 'updated_at' => '2025-08-12 14:45:00'),
            array('categoryName' => 'Office Supplies', 'business_id' => '1', 'categoryDescription' => 'Stationery and office materials', 'status' => '1', 'created_at' => '2025-08-13 08:20:00', 'updated_at' => '2025-08-13 08:20:00')
        );

        ExpenseCategory::insert($expense_categories);
    }
}
