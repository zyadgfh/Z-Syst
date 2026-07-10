<?php

namespace Database\Seeders;

use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use Illuminate\Database\Seeder;

class SaleReturnSeeder extends Seeder
{
    public function run(): void
    {
        $sale_returns = array(
            array('business_id' => '1', 'sale_id' => '3', 'invoice_no' => 'SR01', 'return_date' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('business_id' => '1', 'sale_id' => '2', 'invoice_no' => 'SR02', 'return_date' => now(), 'created_at' => now(), 'updated_at' => now()),
            array('business_id' => '1', 'sale_id' => '1', 'invoice_no' => 'SR03', 'return_date' => now(), 'created_at' => now(), 'updated_at' => now())
        );

        SaleReturn::insert($sale_returns);

        $sale_return_details = array(
            array('business_id' => '1', 'sale_return_id' => '1', 'sale_detail_id' => '3', 'return_amount' => '451.43', 'return_qty' => '2.00'),
            array('business_id' => '1', 'sale_return_id' => '2', 'sale_detail_id' => '2', 'return_amount' => '623.70', 'return_qty' => '3.00'),
            array('business_id' => '1', 'sale_return_id' => '3', 'sale_detail_id' => '1', 'return_amount' => '296.67', 'return_qty' => '1.00')
        );

        SaleReturnDetails::insert($sale_return_details);
    }
}
