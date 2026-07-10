<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = array(
            array('business_id' => '1', 'product_id' => '1', 'batch_no' => NULL, 'productStock' => '4994', 'productPurchasePrice' => '100', 'profit_percent' => '0', 'productSalePrice' => '200', 'productWholeSalePrice' => '180', 'productDealerPrice' => '150', 'mfg_date' => NULL, 'expire_date' => NULL, 'created_at' => '2025-07-28 14:53:49', 'updated_at' => '2025-07-28 14:53:49'),
            array('business_id' => '1', 'product_id' => '2', 'batch_no' => NULL, 'productStock' => '45', 'productPurchasePrice' => '200', 'profit_percent' => '0', 'productSalePrice' => '300', 'productWholeSalePrice' => '250', 'productDealerPrice' => '220', 'mfg_date' => NULL, 'expire_date' => NULL, 'created_at' => '2025-07-28 14:54:05', 'updated_at' => '2025-07-28 14:54:05'),
            array('business_id' => '1', 'product_id' => '3', 'batch_no' => NULL, 'productStock' => '45', 'productPurchasePrice' => '50', 'profit_percent' => '5', 'productSalePrice' => '100', 'productWholeSalePrice' => '90', 'productDealerPrice' => '70', 'mfg_date' => '2025-07-28', 'expire_date' => '2025-08-09', 'created_at' => '2025-07-28 14:54:24', 'updated_at' => '2025-07-28 14:54:24'),
            array('business_id' => '1', 'product_id' => '4', 'batch_no' => NULL, 'productStock' => '56', 'productPurchasePrice' => '220', 'profit_percent' => '6', 'productSalePrice' => '270', 'productWholeSalePrice' => '250', 'productDealerPrice' => '230', 'mfg_date' => '2025-07-28', 'expire_date' => '2025-08-06', 'created_at' => '2025-07-28 14:54:54', 'updated_at' => '2025-07-28 14:54:54'),
            array('business_id' => '1', 'product_id' => '5', 'batch_no' => NULL, 'productStock' => '9000', 'productPurchasePrice' => '10', 'profit_percent' => '200', 'productSalePrice' => '30', 'productWholeSalePrice' => '25', 'productDealerPrice' => '20', 'mfg_date' => NULL, 'expire_date' => NULL, 'created_at' => '2025-08-11 17:48:55', 'updated_at' => '2025-08-11 17:48:55'),
            array('business_id' => '1', 'product_id' => '6', 'batch_no' => NULL, 'productStock' => '9997', 'productPurchasePrice' => '80', 'profit_percent' => '50', 'productSalePrice' => '120', 'productWholeSalePrice' => '100', 'productDealerPrice' => '100', 'mfg_date' => NULL, 'expire_date' => NULL, 'created_at' => '2025-08-11 17:50:21', 'updated_at' => '2025-08-20 00:34:33'),
            array('business_id' => '1', 'product_id' => '7', 'batch_no' => NULL, 'productStock' => '8995', 'productPurchasePrice' => '20', 'profit_percent' => '50', 'productSalePrice' => '30', 'productWholeSalePrice' => '40', 'productDealerPrice' => '0', 'mfg_date' => NULL, 'expire_date' => NULL, 'created_at' => '2025-08-11 17:51:50', 'updated_at' => '2025-08-20 00:35:32'),
            array('business_id' => '1', 'product_id' => '8', 'batch_no' => NULL, 'productStock' => '50018', 'productPurchasePrice' => '22', 'profit_percent' => '100', 'productSalePrice' => '40', 'productWholeSalePrice' => '0', 'productDealerPrice' => '0', 'mfg_date' => NULL, 'expire_date' => NULL, 'created_at' => '2025-08-11 18:12:53', 'updated_at' => '2025-08-11 18:12:53')
        );

        Stock::insert($stocks);
    }
}
