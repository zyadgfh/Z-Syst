<?php

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $expenses = array(
            array('amount' => '577.00', 'expense_category_id' => '1', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Purchase Products', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'PPL7842', 'note' => 'Expense for purchases product', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '1500.00', 'expense_category_id' => '2', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Electricity Bill', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'UTL1122', 'note' => 'Monthly electricity expense', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '2500.00', 'expense_category_id' => '3', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Staff Salary', 'paymentType' => NULL, 'payment_type_id' => '3', 'referenceNo' => 'SAL5566', 'note' => 'Salary payment for staff', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '700.00', 'expense_category_id' => '4', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Air Conditioner Repair', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'MTN7788', 'note' => 'Maintenance expense for AC', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '300.00', 'expense_category_id' => '2', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Stationery Purchase', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'OFF2244', 'note' => 'Office supplies purchase', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '450.00', 'expense_category_id' => '1', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Raw Materials', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'PPL9900', 'note' => 'Expense for raw materials purchase', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '900.00', 'expense_category_id' => '4', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Plumbing Repair', 'paymentType' => NULL, 'payment_type_id' => '3', 'referenceNo' => 'MTN3344', 'note' => 'Maintenance for plumbing', 'expenseDate' => now(), 'created_at' => now(), 'updated_at' => now())
        );

        Expense::insert($expenses);
    }
}
