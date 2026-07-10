<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\Party;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\SaleReturn;
use App\Models\SaleDetails;
use App\Models\IncomeCategory;
use App\Models\ExpenseCategory;
use App\Models\PurchaseDetails;
use Illuminate\Database\Seeder;
use App\Models\SaleReturnDetails;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $current_date = now();
        $expire_date = now()->addDays(30);

        $suppliers = array(
            array('name' => 'Chase Farmer', 'business_id' => '1', 'email' => 'chase@mailinator.com', 'type' => 'Supplier', 'phone' => '01798765432', 'due' => '400.00', 'address' => 'Road 4, Mohammadpur, Dhaka', 'image' => 'uploads/25/08/1755065778-932.jpg', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('name' => 'Liam Carter', 'business_id' => '1', 'email' => 'liamc@mailinator.com', 'type' => 'Supplier', 'phone' => '01911223344', 'due' => '0.00', 'address' => 'House 5, Road 3, Gulshan, Dhaka', 'image' => 'uploads/25/08/1755065828-624.jpg', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('name' => 'Olivia Smith', 'business_id' => '1', 'email' => 'olivias@mailinator.com', 'type' => 'Supplier', 'phone' => '01833445566', 'due' => '120.00', 'address' => 'Road 7, Banani, Dhaka', 'image' => 'uploads/25/08/1755065837-663.jpg', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
        );

        $customers = array(
            array('name' => 'Amber Bush', 'business_id' => '1', 'email' => 'amber@mailinator.com', 'type' => 'Retailer', 'phone' => '01711234567', 'due' => '0.00', 'address' => 'House 12, Road 5, Dhanmondi, Dhaka', 'image' => 'uploads/25/08/1755065759-870.jpg', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('name' => 'Zoe Kidd', 'business_id' => '1', 'email' => 'Zoeid@mailinator.com', 'type' => 'Dealer', 'phone' => '01876543210', 'due' => '0.00', 'address' => 'Apartment 7B, Banani, Dhaka', 'image' => 'uploads/25/08/1755065746-342.jpg', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('name' => 'Porter Flynn', 'business_id' => '1', 'email' => 'porter@mailinator.com', 'type' => 'Wholesaler', 'phone' => '01912345678', 'due' => '670.00', 'address' => 'House 22, Road 9, Uttara, Dhaka', 'image' => 'uploads/25/08/1755065735-399.jpg', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
        );

        $sales = array(
            array('business_id' => '1', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '6.67', 'shipping_charge' => '0', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '0.00', 'isPaid' => '1', 'vat_amount' => '45.00', 'vat_percent' => '0.00', 'paidAmount' => '638.33', 'change_amount' => '0', 'totalAmount' => '638.33', 'actual_total_amount' => '638.33', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '193.33', 'paymentType' => NULL, 'payment_type_id' => '1', 'invoiceNumber' => 'S01', 'saleDate' => '2025-08-13 00:00:00', 'image' => NULL, 'meta' => ['customer_phone' => null, 'note' => 'paid done'], 'created_at' => $current_date, 'updated_at' => $current_date, 'vat_id' => '1'),
            array('business_id' => '1', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '36.30', 'shipping_charge' => '0', 'discount_percent' => '5', 'discount_type' => 'percent', 'dueAmount' => '755.70', 'isPaid' => '0', 'vat_amount' => '132.00', 'vat_percent' => '0.00', 'paidAmount' => '0.00', 'change_amount' => '0', 'totalAmount' => '755.70', 'actual_total_amount' => '755.70', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '23.70', 'paymentType' => NULL, 'payment_type_id' => '5', 'invoiceNumber' => 'S02', 'saleDate' => '2025-08-13 00:00:00', 'image' => NULL, 'meta' => ['customer_phone' => null, 'note' => 'kepp due payment'], 'created_at' => $current_date, 'updated_at' => $current_date, 'vat_id' => '2'),
            array('business_id' => '1', 'user_id' => '4', 'type' => 'sale', 'discountAmount' => '21.43', 'shipping_charge' => '40', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '849.07', 'isPaid' => '0', 'vat_amount' => '80.50', 'vat_percent' => '0.00', 'paidAmount' => '0.00', 'change_amount' => '0', 'totalAmount' => '1249.07', 'actual_total_amount' => '1249.07', 'rounding_amount' => '0.00', 'rounding_option' => 'none', 'lossProfit' => '28.57', 'paymentType' => NULL, 'payment_type_id' => '1', 'invoiceNumber' => 'S03', 'saleDate' => '2025-08-13 00:00:00', 'image' => NULL, 'meta' => ['customer_phone' => null, 'note' => null], 'created_at' => $current_date, 'updated_at' => $current_date, 'vat_id' => '1'),
        );

        $sale_details = array(
            array('product_id' => '2', 'price' => '300.00', 'lossProfit' => '193.33', 'quantities' => '2.00', 'productPurchasePrice' => '200', 'mfg_date' => NULL, 'expire_date' => $expire_date, 'stock_id' => '2'),
            array('product_id' => '2', 'price' => '220.00', 'lossProfit' => '23.70', 'quantities' => '3.00', 'productPurchasePrice' => '200', 'mfg_date' => NULL, 'expire_date' => $expire_date, 'stock_id' => '2'),
            array('product_id' => '4', 'price' => '230.00', 'lossProfit' => '28.57', 'quantities' => '5.00', 'productPurchasePrice' => '220', 'mfg_date' => NULL, 'expire_date' => $expire_date, 'stock_id' => '4'),
        );

        $sale_returns = array(
            array('business_id' => '1', 'invoice_no' => 'SR01', 'return_date' => '2025-08-13 12:25:00', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('business_id' => '1', 'invoice_no' => 'SR02', 'return_date' => '2025-08-13 12:25:18', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('business_id' => '1', 'invoice_no' => 'SR03', 'return_date' => '2025-08-13 12:25:36', 'created_at' => $current_date, 'updated_at' => $current_date)
        );

        $sale_return_details = array(
            array('business_id' => '1', 'return_amount' => '451.43', 'return_qty' => '2.00'),
            array('business_id' => '1', 'return_amount' => '623.70', 'return_qty' => '3.00'),
            array('business_id' => '1', 'return_amount' => '296.67', 'return_qty' => '1.00')
        );

        $purchases = array(
            array('business_id' => '1', 'user_id' => '4', 'discountAmount' => '20.73', 'shipping_charge' => '21', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '0.00', 'paidAmount' => '710.27', 'change_amount' => '0', 'totalAmount' => '710.27', 'invoiceNumber' => 'P01', 'isPaid' => '1', 'vat_percent' => '0.00', 'vat_amount' => '110.00', 'paymentType' => NULL, 'payment_type_id' => '1', 'purchaseDate' => '2025-08-13 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date, 'vat_id' => '2'),
            array('business_id' => '1', 'user_id' => '4', 'discountAmount' => '143.00', 'shipping_charge' => '200', 'discount_percent' => '5', 'discount_type' => 'percent', 'dueAmount' => '3097.00', 'paidAmount' => '0.00', 'change_amount' => '0', 'totalAmount' => '3097.00', 'invoiceNumber' => 'P02', 'isPaid' => '0', 'vat_percent' => '0.00', 'vat_amount' => '440.00', 'paymentType' => NULL, 'payment_type_id' => '5', 'purchaseDate' => '2025-08-13 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date, 'vat_id' => '2'),
            array('business_id' => '1', 'user_id' => '4', 'discountAmount' => '20.12', 'shipping_charge' => '45', 'discount_percent' => '0', 'discount_type' => 'flat', 'dueAmount' => '314.88', 'paidAmount' => '52.88', 'change_amount' => '0', 'totalAmount' => '414.88', 'invoiceNumber' => 'P03', 'isPaid' => '0', 'vat_percent' => '0.00', 'vat_amount' => '40.00', 'paymentType' => NULL, 'payment_type_id' => '2', 'purchaseDate' => '2025-08-13 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date, 'vat_id' => '2'),
        );

        $purchase_details = array(
            array('product_id' => '1', 'productDealerPrice' => '150.00', 'productPurchasePrice' => '100.00', 'productSalePrice' => '200.00', 'productWholeSalePrice' => '180.00', 'quantities' => '6.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => $expire_date, 'stock_id' => '1'),
            array('product_id' => '2', 'productDealerPrice' => '220.00', 'productPurchasePrice' => '200.00', 'productSalePrice' => '300.00', 'productWholeSalePrice' => '250.00', 'quantities' => '13.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => $expire_date, 'stock_id' => '2'),
            array('product_id' => '3', 'productDealerPrice' => '70.00', 'productPurchasePrice' => '50.00', 'productSalePrice' => '100.00', 'productWholeSalePrice' => '90.00', 'quantities' => '7.00', 'mfg_date' => NULL, 'profit_percent' => NULL, 'expire_date' => $expire_date, 'stock_id' => '3'),
        );

        foreach ($sales as $key => $sale) {

            $customer = Party::create($customers[$key]);
            $supplier = Party::create($suppliers[$key]);

            $sale_data = Sale::create($sale + [
                'party_id' => $customer->id
            ]);

            $sale_detail = SaleDetails::create($sale_details[$key] + [
                'sale_id' => $sale_data->id
            ]);

            $sale_return_data = SaleReturn::create($sale_returns[$key] + [
                'sale_id' => $sale_data->id
            ]);

            SaleReturnDetails::create($sale_return_details[$key] + [
                'sale_return_id' => $sale_return_data->id,
                'sale_detail_id' => $sale_detail->id
            ]);

            $purchase_data = Purchase::create($purchases[$key] + [
                'party_id' => $supplier->id
            ]);
            PurchaseDetails::create($purchase_details[$key] + [
                'purchase_id' => $purchase_data->id
            ]);
        }

        $expense_categories = array(
            array('categoryName' => 'Purchase', 'business_id' => '1', 'categoryDescription' => 'Expenses on purchasing goods', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('categoryName' => 'Utilities', 'business_id' => '1', 'categoryDescription' => 'Electricity, water, and gas bills', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('categoryName' => 'Salary', 'business_id' => '1', 'categoryDescription' => 'Employee salary payments', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
        );

        $expenses = array(
            array('amount' => '577.00', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Purchase Products', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'PPL7842', 'note' => 'Expense for purchases product', 'expenseDate' => '2025-08-13 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('amount' => '1500.00', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Electricity Bill', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'UTL1122', 'note' => 'Monthly electricity expense', 'expenseDate' => '2025-08-12 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('amount' => '2500.00', 'user_id' => '4', 'business_id' => '1', 'expanseFor' => 'Staff Salary', 'paymentType' => NULL, 'payment_type_id' => '3', 'referenceNo' => 'SAL5566', 'note' => 'Salary payment for staff', 'expenseDate' => '2025-08-11 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date),
        );

        foreach ($expense_categories as $key => $expense_category) {
            $expenses_category = ExpenseCategory::create($expense_category);
            Expense::create($expenses[$key] + [
                'expense_category_id' => $expenses_category->id
            ]);
        }

        $income_categories = array(
            array('categoryName' => 'Sales', 'business_id' => '1', 'categoryDescription' => 'Product sales income', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('categoryName' => 'Service', 'business_id' => '1', 'categoryDescription' => 'Income from services', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('categoryName' => 'Rental', 'business_id' => '1', 'categoryDescription' => 'Rental income', 'status' => '1', 'created_at' => $current_date, 'updated_at' => $current_date),
        );

        $incomes = array(
            array('amount' => '4700.00', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Sales Product', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'SPT8734', 'note' => 'Income for selling products', 'incomeDate' => '2025-08-13 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('amount' => '3500.00', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Website Service', 'paymentType' => NULL, 'payment_type_id' => '2', 'referenceNo' => 'SRV1920', 'note' => 'Income from web services', 'incomeDate' => '2025-08-05 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date),
            array('amount' => '5000.00', 'user_id' => '4', 'business_id' => '1', 'incomeFor' => 'Office Rent', 'paymentType' => NULL, 'payment_type_id' => '1', 'referenceNo' => 'RNT3245', 'note' => 'Monthly office rent', 'incomeDate' => '2025-08-09 00:00:00', 'created_at' => $current_date, 'updated_at' => $current_date),
        );

        foreach ($income_categories as $key => $income_category) {
            $incomes_category = IncomeCategory::create($income_category);
            Income::create($incomes[$key] + [
                'income_category_id' => $incomes_category->id
            ]);
        }
    }
}
