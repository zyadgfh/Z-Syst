<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['productName' => 'Napa', 'business_id' => '1', 'category_id' => '1', 'unit_id' => '1', 'type_id' => '1', 'manufacturer_id' => '1', 'box_size_id' => '1', 'purchase_without_tax' => '100', 'purchase_with_tax' => '110', 'profit_percent' => '20', 'sales_price' => '120', 'alert_qty' => '5', 'wholesale_price' => '115', 'productCode' => 'ABCD', 'images' => null, 'tax_id' => '1', 'tax_type' => 'exclusive', 'meta' => '{"strength":"1 KG","generic_name":"Nepa","shelf":"A1","medicine_details":"Nothing for this."}', 'created_at' => '2024-12-09 09:46:08', 'updated_at' => '2024-12-09 09:46:08'],
        ];

        $stocks = [
            ['business_id' => '1', 'product_id' => 1, 'productStock' => 10, 'batch_no' => 'ASSS', 'expire_date' => '2024-11-22', 'created_at' => '2024-12-09 09:50:27', 'updated_at' => '2024-12-09 09:50:27'],
        ];

        Product::insert($products);
        Stock::insert($stocks);
    }
}
