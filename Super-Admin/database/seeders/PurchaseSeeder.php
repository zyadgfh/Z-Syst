<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\PurchaseDetails;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $purchases = array(
            array('party_id' => '10', 'business_id' => '1', 'user_id' => '4', 'discountAmount' => '20.73', 'shipping_charge' => '21', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '0.00', 'paidAmount' => '710.27', 'change_amount' => '0', 'totalAmount' => '710.27', 'invoiceNumber' => 'P01', 'isPaid' => '1', 'vat_percent' => '0.00', 'vat_amount' => '110.00', 'paymentType' => NULL, 'payment_type_id' => '1', 'purchaseDate' => now()->subDays(), 'created_at' => now()->subDays(), 'updated_at' => now()->subDays(), 'vat_id' => '2'),
            array('party_id' => '4', 'business_id' => '1', 'user_id' => '4', 'discountAmount' => '143.00', 'shipping_charge' => '200', 'discount_percent' => '5', 'discount_type' => 'percent', 'dueAmount' => '97.00', 'paidAmount' => '0.00', 'change_amount' => '0', 'totalAmount' => '97.00', 'invoiceNumber' => 'P02', 'isPaid' => '0', 'vat_percent' => '0.00', 'vat_amount' => '440.00', 'paymentType' => NULL, 'payment_type_id' => '5', 'purchaseDate' => now(), 'created_at' => now(), 'updated_at' => now(), 'vat_id' => '2'),
            array('party_id' => '6', 'business_id' => '1', 'user_id' => '4', 'discountAmount' => '20.12', 'shipping_charge' => '45', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '314.88', 'paidAmount' => '52.88', 'change_amount' => '0', 'totalAmount' => '414.88', 'invoiceNumber' => 'P03', 'isPaid' => '0', 'vat_percent' => '0.00', 'vat_amount' => '40.00', 'paymentType' => NULL, 'payment_type_id' => '2', 'purchaseDate' => now(), 'created_at' => now(), 'updated_at' => now(), 'vat_id' => '2'),
            array('party_id' => '9', 'business_id' => '1', 'user_id' => '4', 'discountAmount' => '287.98', 'shipping_charge' => '40', 'discount_percent' => '7', 'discount_type' => 'percent', 'dueAmount' => '0.00', 'paidAmount' => '3866.02', 'change_amount' => '133.98', 'totalAmount' => '3866.02', 'invoiceNumber' => 'P04', 'isPaid' => '1', 'vat_percent' => '0.00', 'vat_amount' => '374.00', 'paymentType' => NULL, 'payment_type_id' => '3', 'purchaseDate' => now(), 'created_at' => now(), 'updated_at' => now(), 'vat_id' => '2')
        );

        Purchase::insert($purchases);

        $purchase_details = array(
            array('purchase_id' => '1', 'product_id' => '1', 'productDealerPrice' => '150.00', 'productPurchasePrice' => '100.00', 'productSalePrice' => '200.00', 'productWholeSalePrice' => '180.00', 'quantities' => '6.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => '2031-06-13', 'stock_id' => '1'),
            array('purchase_id' => '2', 'product_id' => '2', 'productDealerPrice' => '220.00', 'productPurchasePrice' => '200.00', 'productSalePrice' => '300.00', 'productWholeSalePrice' => '250.00', 'quantities' => '13.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => '2028-06-13', 'stock_id' => '2'),
            array('purchase_id' => '3', 'product_id' => '3', 'productDealerPrice' => '70.00', 'productPurchasePrice' => '50.00', 'productSalePrice' => '100.00', 'productWholeSalePrice' => '90.00', 'quantities' => '7.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => '2035-11-15', 'stock_id' => '3'),
            array('purchase_id' => '4', 'product_id' => '4', 'productDealerPrice' => '230.00', 'productPurchasePrice' => '220.00', 'productSalePrice' => '270.00', 'productWholeSalePrice' => '250.00', 'quantities' => '17.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => '2027-11-25', 'stock_id' => '4')
        );

        PurchaseDetails::insert($purchase_details);
    }
}
