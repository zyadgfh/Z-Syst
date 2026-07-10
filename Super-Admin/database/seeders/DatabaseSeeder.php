<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
            BusinessSeeder::class,
            PermissionSeeder::class,
            OptionTableSeeder::class,
            BlogSeeder::class,
            UserSeeder::class,
            FeatureSeeder::class,
            InterfaceSeeder::class,
            LanguageSeeder::class,
            TestimonialSeeder::class,
            CurrencySeeder::class,
            GatewaySeeder::class,
            PlanSubscribeSeeder::class,
            AdvertiseSeeder::class,
            BrandSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            // WarehouseSeeder::class,
            ProductSeeder::class,
            PartySeeder::class,
//            PaymentTypeSeeder::class,
            // AffiliateSeeder::class,
            VariationSeeder::class,
            StockSeeder::class,
            ComboProductSeeder::class,
            VatSeeder::class,
            IncomeCategorySeeder::class,
            IncomeSeeder::class,
            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,
            SaleSeeder::class,
            SaleReturnSeeder::class,
            PurchaseSeeder::class,
            PurchaseReturnSeeder::class,
            ShelfSeeder::class,
        ]);
    }
}
