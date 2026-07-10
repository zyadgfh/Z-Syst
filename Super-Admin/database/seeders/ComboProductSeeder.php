<?php

namespace Database\Seeders;

use App\Models\ComboProduct;
use Illuminate\Database\Seeder;

class ComboProductSeeder extends Seeder
{
    public function run(): void
    {
        $combo_products = array(
            array('product_id' => '9', 'stock_id' => '6', 'purchase_price' => '80.000', 'quantity' => '3.000'),
            array('product_id' => '9', 'stock_id' => '3', 'purchase_price' => '50.000', 'quantity' => '2.000'),
            array('product_id' => '10', 'stock_id' => '2', 'purchase_price' => '200.000', 'quantity' => '1.000'),
            array('product_id' => '10', 'stock_id' => '1', 'purchase_price' => '100.000', 'quantity' => '1.000')
        );

        ComboProduct::insert($combo_products);
    }
}
