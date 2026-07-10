<?php

namespace Database\Seeders;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use Illuminate\Database\Seeder;

class PurchaseReturnSeeder extends Seeder
{
    public function run(): void
    {
        $purchase_returns = array(
            array('business_id' => '1', 'purchase_id' => '1', 'invoice_no' => 'PR01', 'return_date' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('business_id' => '1', 'purchase_id' => '2', 'invoice_no' => 'PR02', 'return_date' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('business_id' => '1', 'purchase_id' => '3', 'invoice_no' => 'PR03', 'return_date' => now(), 'created_at' => now(), 'updated_at' => now())
        );

        PurchaseReturn::insert($purchase_returns);

        $purchase_return_details = array(
            array('business_id' => '1', 'purchase_return_id' => '1', 'purchase_detail_id' => '1', 'return_amount' => '482.73', 'return_qty' => '5.00'),
            array('business_id' => '1', 'purchase_return_id' => '2', 'purchase_detail_id' => '2', 'return_amount' => '1701.00', 'return_qty' => '9.00'),
            array('business_id' => '1', 'purchase_return_id' => '3', 'purchase_detail_id' => '3', 'return_amount' => '47.12', 'return_qty' => '1.00')
        );

        PurchaseReturnDetail::insert($purchase_return_details);
    }
}
