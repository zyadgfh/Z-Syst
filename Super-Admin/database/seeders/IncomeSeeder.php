<?php

namespace Database\Seeders;

use App\Models\Income;
use Illuminate\Database\Seeder;

class IncomeSeeder extends Seeder
{
    public function run(): void
    {
        $incomes = array(
            array('amount' => '4700.00', 'income_category_id' => '1', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Sales Product', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'SPT8734', 'note' => 'Income for selling products', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '3500.00', 'income_category_id' => '2', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Website Service', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'SRV1920', 'note' => 'Income from web services', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '5000.00', 'income_category_id' => '3', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Office Rent', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'RNT3245', 'note' => 'Monthly office rent', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '1500.00', 'income_category_id' => '4', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Affiliate Commission', 'paymentType' => NULL, 'payment_type_id' => '3', 'referenceNo' => 'COM7788', 'note' => 'Commission for referrals', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '2100.00', 'income_category_id' => '5', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Bank Interest', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'INT5566', 'note' => 'Interest from bank deposit', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '1200.00', 'income_category_id' => '6', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Supplier Refund', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'RFD4411', 'note' => 'Refund from supplier', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('amount' => '2300.00', 'income_category_id' => '7', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Miscellaneous Income', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'OTH3322', 'note' => 'Other miscellaneous income', 'incomeDate' => now(), 'created_at' => now(), 'updated_at' => now())
        );

        Income::insert($incomes);
    }
}
