<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\SaleDetails;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $sales = array(
            array('business_id' => '1', 'party_id' => '14', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '6.67', 'shipping_charge' => '0', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '0.00', 'isPaid' => '1', 'vat_amount' => '45.00', 'vat_percent' => '0.00', 'paidAmount' => '638.33', 'change_amount' => '0', 'totalAmount' => '638.33', 'actual_total_amount' => '638.33', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '193.33', 'paymentType' => NULL, 'payment_type_id' => '1', 'invoiceNumber' => 'S01', 'saleDate' => now()->subDays(), 'image' => NULL, 'meta' => '{"customer_phone":null,"note":"paid done"}', 'created_at' => now()->subDays(), 'updated_at' => now()->subDays(), 'vat_id' => '1'),
            array('business_id' => '1', 'party_id' => '2', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '36.30', 'shipping_charge' => '0', 'discount_percent' => '5', 'discount_type' => 'percent', 'dueAmount' => '755.70', 'isPaid' => '0', 'vat_amount' => '132.00', 'vat_percent' => '0.00', 'paidAmount' => '0.00', 'change_amount' => '0', 'totalAmount' => '755.70', 'actual_total_amount' => '755.70', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '23.70', 'paymentType' => NULL, 'payment_type_id' => '5', 'invoiceNumber' => 'S02', 'saleDate' => now()->subDays(), 'image' => NULL, 'meta' => '{"customer_phone":null,"note":"kepp due payment"}', 'created_at' => now()->subDays(), 'updated_at' => now()->subDays(), 'vat_id' => '2'),
            array('business_id' => '1', 'party_id' => '15', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '21.43', 'shipping_charge' => '40', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '849.07', 'isPaid' => '0', 'vat_amount' => '80.50', 'vat_percent' => '0.00', 'paidAmount' => '0.00', 'change_amount' => '0', 'totalAmount' => '1249.07', 'actual_total_amount' => '1249.07', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '28.57', 'paymentType' => NULL, 'payment_type_id' => '1', 'invoiceNumber' => 'S03', 'saleDate' => now(), 'image' => NULL, 'meta' => '{"customer_phone":null,"note":"I will payment later"}', 'created_at' => now(), 'updated_at' => now(), 'vat_id' => '1'),
            array('business_id' => '1', 'party_id' => '5', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '47.25', 'shipping_charge' => '46', 'discount_percent' => '5', 'discount_type' => 'percent', 'dueAmount' => '0.00', 'isPaid' => '1', 'vat_amount' => '45.00', 'vat_percent' => '0.00', 'paidAmount' => '943.75', 'change_amount' => '756.25', 'totalAmount' => '943.75', 'actual_total_amount' => '943.75', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '402.75', 'paymentType' => NULL, 'payment_type_id' => '1', 'invoiceNumber' => 'S04', 'saleDate' => now(), 'image' => NULL, 'meta' => '{"customer_phone":null,"note":null}', 'created_at' => now(), 'updated_at' => now(), 'vat_id' => '1')
        );

        Sale::insert($sales);

        $sale_details = array(
            array('sale_id' => '1', 'product_id' => '2', 'price' => '300.00', 'lossProfit' => '193.33', 'quantities' => '2.00', 'productPurchasePrice' => '200', 'mfg_date' => NULL, 'expire_date' => NULL, 'stock_id' => '2'),
            array('sale_id' => '2', 'product_id' => '2', 'price' => '220.00', 'lossProfit' => '23.70', 'quantities' => '3.00', 'productPurchasePrice' => '200', 'mfg_date' => NULL, 'expire_date' => NULL, 'stock_id' => '2'),
            array('sale_id' => '3', 'product_id' => '4', 'price' => '230.00', 'lossProfit' => '28.57', 'quantities' => '5.00', 'productPurchasePrice' => '220', 'mfg_date' => NULL, 'expire_date' => '2025-08-06', 'stock_id' => '4'),
            array('sale_id' => '4', 'product_id' => '3', 'price' => '100.00', 'lossProfit' => '402.75', 'quantities' => '9.00', 'productPurchasePrice' => '50', 'mfg_date' => NULL, 'expire_date' => '2025-08-09', 'stock_id' => '3')
        );

        SaleDetails::insert($sale_details);
    }
}
