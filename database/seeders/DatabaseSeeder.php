<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            BusinessCategorySeeder::class,
            PermissionSeeder::class,
            OptionTableSeeder::class,
            LanguageSeeder::class,
            CurrencySeeder::class,
            GatewaySeeder::class,
            AdvertiseSeeder::class,
            CompanySeeder::class,
            BranchSeeder::class,
            BusinessSeeder::class,
            BoxSizeSeeder::class,
            CategorySeeder::class,
            ManufacturerSeeder::class,
            MedicineTypeSeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            PatientSeeder::class,
            DoctorSeeder::class,
            DepartmentSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            ProductStockSeeder::class,
            PurchaseOrderSeeder::class,
            PurchaseOrderItemSeeder::class,
            GoodsReceivedNoteSeeder::class,
            GrnItemSeeder::class,
            SaleSeeder::class,
            SaleItemSeeder::class,
            StockTransferSeeder::class,
            StockTransferItemSeeder::class,
            CashRegisterSeeder::class,
            DueCollectSeeder::class,
            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,
            IncomeCategorySeeder::class,
            IncomeSeeder::class,
            InsuranceCompanySeeder::class,
            InsurancePlanSeeder::class,
            InsuranceClaimSeeder::class,
            NotificationSeeder::class,
            OptionSeeder::class,
            PlanFeatureSeeder::class,
        ]);
    }
}
