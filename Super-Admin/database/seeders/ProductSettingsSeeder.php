<?php

namespace Database\Seeders;

use App\Models\ProductSetting;
use Illuminate\Database\Seeder;

class ProductSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product_settings = array(
            array('id' => '1', 'business_id' => '1', 'modules' => '{"show_product_name":"1","show_product_price":"1","show_product_code":"1","show_product_stock":"1","show_product_unit":"1","show_product_brand":"1","show_product_category":"1","show_product_manufacturer":"1","show_product_image":"1","show_alert_qty":"1","show_vat_id":"1","show_vat_type":"1","show_exclusive_price":"1","show_inclusive_price":"1","show_profit_percent":"1","show_capacity":"1","show_weight":"1","show_color":"1","show_type":"1","show_size":"1","show_warehouse":"1","show_action":"1","show_product_sale_price":"1","default_sale_price":null,"show_product_wholesale_price":"1","default_wholesale_price":null,"show_product_dealer_price":"1","default_dealer_price":null,"show_batch_no":"1","default_batch_no":null,"show_expire_date":"1","expire_date_type":"dmy","show_mfg_date":"1","mfg_date_type":"dmy","show_model_no":"1","show_product_type_single":"1","show_product_type_variant":"1","show_product_batch_no":"1","show_product_expire_date":"1","default_expired_date":null,"default_mfg_date":null}', 'created_at' => '2025-07-29 04:25:50', 'updated_at' => '2025-07-29 04:38:10')
        );

        ProductSetting::insert($product_settings);
    }
}
