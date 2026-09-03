<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Manufacturer;
use App\Models\Party;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // 1. Create business categories
        $categories = $this->seedBusinessCategories();

        // 2. Create plans
        $plans = $this->seedPlans();

        // 3. Create currencies
        $currencies = $this->seedCurrencies();

        // 4. Create business with owner
        $business = $this->seedBusiness($categories->first());

        // 5. Skip branches (references non-existent companies table)

        // 6. Create admin user
        $admin = $this->seedAdmin($business);

        // 7. Create product categories, manufacturers, units
        $productCategories = $this->seedProductCategories($business);
        $manufacturers = $this->seedManufacturers($business);
        $units = $this->seedUnits($business);

        // 8. Create warehouses
        $warehouses = $this->seedWarehouses($business);

        // 9. Create suppliers
        $suppliers = $this->seedSuppliers($business);

        // 10. Create products with stock
        $products = $this->seedProducts($business, $productCategories, $manufacturers, $units, $warehouses);

        // 11. Create sample sales
        $this->seedSales($business, $products, $warehouses);

        // 12. Create sample purchases
        $this->seedPurchases($business, $products, $suppliers, $warehouses);

        // 13. Create customers
        $this->seedCustomers($business);

        // 14. Create stock transfer history
        $this->seedStockTransfers($business, $products, $warehouses);

        $this->command->info('Demo data seeded successfully!');
    }

    private function seedBusinessCategories()
    {
        $cats = ['Pharmacy', 'Hospital', 'Clinic', 'Laboratory'];
        $results = [];
        foreach ($cats as $name) {
            $results[] = BusinessCategory::firstOrCreate(
                ['name' => $name],
                ['status' => 1, 'description' => "$name business"]
            );
        }
        return collect($results);
    }

    private function seedPlans()
    {
        $plans = [
            ['subscriptionName' => 'Basic', 'subscriptionPrice' => 29, 'duration' => 30, 'status' => 1, 'features' => '2 users, 1 branch'],
            ['subscriptionName' => 'Professional', 'subscriptionPrice' => 79, 'duration' => 30, 'status' => 1, 'features' => '10 users, 3 branches'],
            ['subscriptionName' => 'Enterprise', 'subscriptionPrice' => 199, 'duration' => 30, 'status' => 1, 'features' => 'Unlimited users, branches'],
        ];
        $results = [];
        foreach ($plans as $data) {
            $results[] = Plan::firstOrCreate(['subscriptionName' => $data['subscriptionName']], $data);
        }
        return collect($results);
    }

    private function seedCurrencies()
    {
        $currencies = [
            ['name' => 'Egyptian Pound', 'code' => 'EGP', 'symbol' => 'EGP', 'rate' => 1, 'status' => 1, 'is_default' => 1],
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'rate' => 50, 'status' => 1, 'is_default' => 0],
        ];
        $results = [];
        foreach ($currencies as $data) {
            $results[] = Currency::firstOrCreate(['code' => $data['code']], $data);
        }
        return collect($results);
    }

    private function seedBusiness($category)
    {
        return Business::firstOrCreate(
            ['companyName' => 'Z-Syst Demo Pharmacy'],
            [
                'companyName' => 'Z-Syst Demo Pharmacy',
                'business_category_id' => $category->id,
                'phoneNumber' => '+201234567890',
                'address' => '123 Main Street, Cairo, Egypt',
                'will_expire' => now()->addYear()->toDateString(),
            ]
        );
    }

    private function seedBranches($business)
    {
        $branchNames = ['Main Branch', 'Branch 2 - Downtown', 'Branch 3 - Uptown'];
        $results = [];
        foreach ($branchNames as $i => $name) {
            $exists = DB::table('branches')
                ->where('company_id', $business->id)
                ->where('branch_name', $name)
                ->first();
            if (!$exists) {
                $id = DB::table('branches')->insertGetId([
                    'company_id' => $business->id,
                    'branch_name' => $name,
                    'branch_code' => 'BR-' . ($i + 1),
                    'address' => "$name, Cairo",
                    'phone' => '+20123456789' . $i,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $results[] = (object)['id' => $id, 'branch_name' => $name];
            } else {
                $results[] = $exists;
            }
        }
        return collect($results);
    }

    private function seedAdmin($business)
    {
        return User::firstOrCreate(
            ['email' => 'admin@z-syst.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'business_id' => $business->id,
                'role' => 'shop-owner',
                'phone' => '+201000000000',
                'status' => 'active',
            ]
        );
    }

    private function seedProductCategories($business)
    {
        $names = ['Analgesics', 'Antibiotics', 'Antihistamines', 'Antidiabetics', 'Cardiovascular',
                  'Gastrointestinal', 'Respiratory', 'Dermatological', 'Vitamins', 'Eye Care'];
        $results = [];
        foreach ($names as $name) {
            $results[] = Category::firstOrCreate(
                ['business_id' => $business->id, 'categoryName' => $name],
                ['status' => 1, 'business_id' => $business->id]
            );
        }
        return collect($results);
    }

    private function seedManufacturers($business)
    {
        $names = ['Pharco', 'EVA Pharma', 'Memphis Pharma', 'Amoun', 'Chemipharm', 'Sigma'];
        $results = [];
        foreach ($names as $name) {
            $results[] = Manufacturer::firstOrCreate(
                ['business_id' => $business->id, 'name' => $name],
                ['status' => 1, 'description' => "$name Pharmaceutical"]
            );
        }
        return collect($results);
    }

    private function seedUnits($business)
    {
        $units = [
            ['unitName' => 'Tablet'],
            ['unitName' => 'Capsule'],
            ['unitName' => 'Bottle'],
            ['unitName' => 'Tube'],
            ['unitName' => 'Injection'],
            ['unitName' => 'Box'],
        ];
        $results = [];
        foreach ($units as $data) {
            $results[] = Unit::firstOrCreate(
                ['business_id' => $business->id, 'unitName' => $data['unitName']],
                ['business_id' => $business->id, 'status' => 1]
            );
        }
        return collect($results);
    }

    private function seedWarehouses($business)
    {
        $warehouses = [
            ['name' => 'Main Warehouse', 'code' => 'WH-001', 'location' => 'Cairo', 'is_default' => true, 'is_active' => true],
            ['name' => 'Downtown Store', 'code' => 'WH-002', 'location' => 'Downtown Cairo', 'is_default' => false, 'is_active' => true],
            ['name' => 'Uptown Store', 'code' => 'WH-003', 'location' => 'Uptown Cairo', 'is_default' => false, 'is_active' => true],
        ];
        $results = [];
        foreach ($warehouses as $data) {
            $results[] = Warehouse::firstOrCreate(
                ['business_id' => $business->id, 'code' => $data['code']],
                array_merge($data, ['business_id' => $business->id])
            );
        }
        return collect($results);
    }

    private function seedSuppliers($business)
    {
        $suppliers = [
            ['name' => 'Al-Dawapharma', 'phone' => '+201111111111', 'email' => 'info@aldawa.com'],
            ['name' => 'Sedico Trading', 'phone' => '+201222222222', 'email' => 'sales@sedico.com'],
            ['name' => 'Global Pharma', 'phone' => '+201333333333', 'email' => 'orders@globalpharma.com'],
        ];
        $results = [];
        foreach ($suppliers as $data) {
            $results[] = Party::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                array_merge($data, ['business_id' => $business->id, 'type' => 'Supplier', 'status' => 1])
            );
        }
        return collect($results);
    }

    private function seedProducts($business, $categories, $manufacturers, $units, $warehouses)
    {
        $drugNames = [
            ['name' => 'Panadol 500mg', 'cat_idx' => 0, 'mfg_idx' => 0, 'unit_idx' => 0, 'price' => 12.50, 'cost' => 8.00],
            ['name' => 'Amoxicillin 500mg', 'cat_idx' => 1, 'mfg_idx' => 1, 'unit_idx' => 0, 'price' => 45.00, 'cost' => 30.00],
            ['name' => 'Cetirizine 10mg', 'cat_idx' => 2, 'mfg_idx' => 2, 'unit_idx' => 0, 'price' => 18.00, 'cost' => 10.00],
            ['name' => 'Metformin 850mg', 'cat_idx' => 3, 'mfg_idx' => 3, 'unit_idx' => 0, 'price' => 25.00, 'cost' => 15.00],
            ['name' => 'Cardace 5mg', 'cat_idx' => 4, 'mfg_idx' => 4, 'unit_idx' => 0, 'price' => 65.00, 'cost' => 45.00],
            ['name' => 'Gastrazol 20mg', 'cat_idx' => 5, 'mfg_idx' => 0, 'unit_idx' => 0, 'price' => 35.00, 'cost' => 20.00],
            ['name' => 'Ventolin Inhaler', 'cat_idx' => 6, 'mfg_idx' => 1, 'unit_idx' => 3, 'price' => 85.00, 'cost' => 55.00],
            ['name' => 'Hydrocortisone Cream', 'cat_idx' => 7, 'mfg_idx' => 2, 'unit_idx' => 3, 'price' => 22.00, 'cost' => 12.00],
            ['name' => 'Centrum Multivitamin', 'cat_idx' => 8, 'mfg_idx' => 3, 'unit_idx' => 5, 'price' => 120.00, 'cost' => 80.00],
            ['name' => 'Tobrex Eye Drops', 'cat_idx' => 9, 'mfg_idx' => 4, 'unit_idx' => 2, 'price' => 55.00, 'cost' => 35.00],
            ['name' => 'Ibuprofen 400mg', 'cat_idx' => 0, 'mfg_idx' => 5, 'unit_idx' => 0, 'price' => 15.00, 'cost' => 9.00],
            ['name' => 'Azithromycin 500mg', 'cat_idx' => 1, 'mfg_idx' => 0, 'unit_idx' => 0, 'price' => 55.00, 'cost' => 38.00],
            ['name' => 'Loratadine 10mg', 'cat_idx' => 2, 'mfg_idx' => 1, 'unit_idx' => 0, 'price' => 20.00, 'cost' => 11.00],
            ['name' => 'Glucophage 1000mg', 'cat_idx' => 3, 'mfg_idx' => 2, 'unit_idx' => 0, 'price' => 42.00, 'cost' => 28.00],
            ['name' => 'Norvasc 5mg', 'cat_idx' => 4, 'mfg_idx' => 3, 'unit_idx' => 0, 'price' => 48.00, 'cost' => 30.00],
            ['name' => 'Augmentin 625mg', 'cat_idx' => 1, 'mfg_idx' => 4, 'unit_idx' => 0, 'price' => 68.00, 'cost' => 48.00],
            ['name' => 'Nexium 40mg', 'cat_idx' => 5, 'mfg_idx' => 5, 'unit_idx' => 0, 'price' => 95.00, 'cost' => 65.00],
            ['name' => 'Concerta 36mg', 'cat_idx' => 0, 'mfg_idx' => 0, 'unit_idx' => 0, 'price' => 180.00, 'cost' => 120.00],
            ['name' => 'Crestor 10mg', 'cat_idx' => 4, 'mfg_idx' => 1, 'unit_idx' => 0, 'price' => 110.00, 'cost' => 75.00],
            ['name' => 'Allegra 180mg', 'cat_idx' => 2, 'mfg_idx' => 2, 'unit_idx' => 0, 'price' => 55.00, 'cost' => 35.00],
            ['name' => 'Dettol Antiseptic', 'cat_idx' => 7, 'mfg_idx' => 3, 'unit_idx' => 2, 'price' => 32.00, 'cost' => 20.00],
            ['name' => 'Dulcolax Suppository', 'cat_idx' => 5, 'mfg_idx' => 4, 'unit_idx' => 0, 'price' => 28.00, 'cost' => 16.00],
            ['name' => 'Flagyl 400mg', 'cat_idx' => 1, 'mfg_idx' => 5, 'unit_idx' => 0, 'price' => 22.00, 'cost' => 13.00],
            ['name' => 'Voltaren Gel', 'cat_idx' => 7, 'mfg_idx' => 0, 'unit_idx' => 3, 'price' => 45.00, 'cost' => 28.00],
            ['name' => 'Fish Oil Omega-3', 'cat_idx' => 8, 'mfg_idx' => 1, 'unit_idx' => 5, 'price' => 95.00, 'cost' => 60.00],
            ['name' => 'Salbutamol Nebulizer', 'cat_idx' => 6, 'mfg_idx' => 2, 'unit_idx' => 2, 'price' => 38.00, 'cost' => 22.00],
            ['name' => 'Clotrimazole Cream', 'cat_idx' => 7, 'mfg_idx' => 3, 'unit_idx' => 3, 'price' => 18.00, 'cost' => 10.00],
            ['name' => 'Calpol 120mg Syrup', 'cat_idx' => 0, 'mfg_idx' => 4, 'unit_idx' => 2, 'price' => 25.00, 'cost' => 15.00],
            ['name' => 'ORS Sachets', 'cat_idx' => 5, 'mfg_idx' => 5, 'unit_idx' => 5, 'price' => 15.00, 'cost' => 8.00],
            ['name' => 'Vitamin D3 5000IU', 'cat_idx' => 8, 'mfg_idx' => 0, 'unit_idx' => 0, 'price' => 85.00, 'cost' => 50.00],
            ['name' => 'Pantoprazole 40mg', 'cat_idx' => 5, 'mfg_idx' => 1, 'unit_idx' => 0, 'price' => 30.00, 'cost' => 18.00],
            ['name' => 'Pregabalin 75mg', 'cat_idx' => 0, 'mfg_idx' => 2, 'unit_idx' => 0, 'price' => 75.00, 'cost' => 50.00],
            ['name' => 'Betnovate Cream', 'cat_idx' => 7, 'mfg_idx' => 3, 'unit_idx' => 3, 'price' => 20.00, 'cost' => 11.00],
            ['name' => 'Micardis 80mg', 'cat_idx' => 4, 'mfg_idx' => 4, 'unit_idx' => 0, 'price' => 120.00, 'cost' => 80.00],
            ['name' => 'Diazepam 5mg', 'cat_idx' => 0, 'mfg_idx' => 5, 'unit_idx' => 0, 'price' => 12.00, 'cost' => 7.00],
            ['name' => 'Levofloxacin 500mg', 'cat_idx' => 1, 'mfg_idx' => 0, 'unit_idx' => 0, 'price' => 60.00, 'cost' => 40.00],
            ['name' => 'Solgar Vitamin C', 'cat_idx' => 8, 'mfg_idx' => 1, 'unit_idx' => 0, 'price' => 150.00, 'cost' => 95.00],
            ['name' => 'Zovirax Cream', 'cat_idx' => 7, 'mfg_idx' => 2, 'unit_idx' => 3, 'price' => 65.00, 'cost' => 40.00],
            ['name' => 'Becotide Nasal Spray', 'cat_idx' => 6, 'mfg_idx' => 3, 'unit_idx' => 2, 'price' => 75.00, 'cost' => 48.00],
        ];

        $products = [];
        foreach ($drugNames as $drug) {
            $profit = round((($drug['price'] - $drug['cost']) / $drug['cost']) * 100, 1);
            $product = Product::firstOrCreate(
                ['business_id' => $business->id, 'productName' => $drug['name']],
                [
                    'business_id' => $business->id,
                    'productName' => $drug['name'],
                    'category_id' => $categories[$drug['cat_idx']]->id,
                    'manufacturer_id' => $manufacturers[$drug['mfg_idx']]->id,
                    'unit_id' => $units[$drug['unit_idx']]->id,
                    'purchase_without_tax' => $drug['cost'],
                    'purchase_with_tax' => round($drug['cost'] * 1.14, 2),
                    'sales_price' => $drug['price'],
                    'wholesale_price' => round($drug['price'] * 0.9, 2),
                    'profit_percent' => $profit,
                    'productCode' => strtoupper(substr(md5($drug['name']), 0, 8)),
                    'alert_qty' => 10,
                ]
            );
            $products[] = $product;

            // Add stock to each warehouse
            foreach ($warehouses as $wh) {
                $qty = mt_rand(20, 200);
                WarehouseStock::firstOrCreate(
                    ['business_id' => $business->id, 'warehouse_id' => $wh->id, 'product_id' => $product->id],
                    ['quantity' => $qty]
                );
            }
        }
        return $products;
    }

    private function seedSales($business, $products, $warehouses)
    {
        $customerNames = ['Ahmed Mohamed', 'Sara Ali', 'Mohamed Hassan', 'Fatma Ibrahim', 'Omar Khalid'];
        $customers = [];

        foreach ($customerNames as $name) {
            $customers[] = Party::firstOrCreate(
                ['business_id' => $business->id, 'name' => $name],
                [
                    'business_id' => $business->id,
                    'type' => 'Retailer',
                    'status' => 1,
                    'phone' => '+201' . mt_rand(100000000, 999999999),
                    'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                ]
            );
        }

        // Create 20 sample sales
        for ($i = 0; $i < 20; $i++) {
            $customer = $customers[array_rand($customers)];
            $numItems = mt_rand(1, 5);
            $subtotal = 0;
            $saleItems = [];

            for ($j = 0; $j < $numItems; $j++) {
                $product = $products[array_rand($products)];
                $qty = mt_rand(1, 5);
                $lineTotal = $product->sales_price * $qty;
                $subtotal += $lineTotal;
                $saleItems[] = compact('product', 'qty', 'lineTotal');
            }

            $tax = round($subtotal * 0.14, 2);
            $total = $subtotal + $tax;
            $paid = $i % 5 === 0 ? $total : round($total * 0.7, 2);
            $due = $total - $paid;

            DB::table('sales')->insert([
                'business_id' => $business->id,
                'party_id' => $customer->id,
                'user_id' => $this->owner->id ?? null,
                'saleDate' => now()->subDays(mt_rand(0, 60)),
                'totalAmount' => $total,
                'paidAmount' => $paid,
                'dueAmount' => $due,
                'tax_amount' => $tax,
                'paymentType' => $paid >= $total ? 'Cash' : 'Credit',
                'isPaid' => $paid >= $total ? 1 : 0,
                'invoiceNumber' => 'INV-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
            ]);

            $saleId = DB::getPdo()->lastInsertId();

            foreach ($saleItems as $item) {                DB::table('sale_details')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $item['product']->id,
                    'quantities' => $item['qty'],
                    'price' => $item['product']->sales_price,
                ]);
            }
        }
    }

    private function seedPurchases($business, $products, $suppliers, $warehouses)
    {
        for ($i = 0; $i < 10; $i++) {
            $supplier = $suppliers[array_rand($suppliers->toArray())];
            $product = $products[array_rand($products)];
            $qty = mt_rand(10, 100);
            $total = $product->purchase_price * $qty;

            DB::table('purchases')->insert([
                'business_id' => $business->id,
                'party_id' => $supplier->id,
                'user_id' => $this->owner->id ?? null,
                'purchaseDate' => now()->subDays(mt_rand(0, 90)),
                'totalAmount' => $total,
                'paidAmount' => $total,
                'paymentType' => 'Cash',
                'isPaid' => 1,
                'invoiceNumber' => 'PUR-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
            ]);

            $purchaseId = DB::getPdo()->lastInsertId();

            DB::table('purchase_details')->insert([
                'purchase_id' => $purchaseId,
                'product_id' => $product->id,
                'quantities' => $qty,
                'purchase_without_tax' => $product->purchase_without_tax,
                'purchase_with_tax' => $product->purchase_with_tax,
                'sales_price' => $product->sales_price,
                'wholesale_price' => $product->wholesale_price,
                'profit_percent' => $product->profit_percent,
            ]);
        }
    }

    private function seedCustomers($business)
    {
        $additionalCustomers = [
            ['name' => 'Online Store Customer', 'phone' => '+201999999999'],
            ['name' => 'Walk-in Customer', 'phone' => '+201888888888'],
        ];

        foreach ($additionalCustomers as $data) {
            Party::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                array_merge($data, ['business_id' => $business->id, 'type' => 'Retailer', 'status' => 1])
            );
        }
    }

    private function seedStockTransfers($business, $products, $warehouses)
    {
        $statuses = ['completed', 'completed', 'completed', 'pending', 'cancelled'];
        $warehousePairs = [];
        foreach ($warehouses as $i => $wh1) {
            foreach ($warehouses as $j => $wh2) {
                if ($i !== $j) {
                    $warehousePairs[] = [$wh1, $wh2];
                }
            }
        }

        for ($i = 0; $i < 15; $i++) {
            $pair = $warehousePairs[array_rand($warehousePairs)];
            $product = $products[array_rand($products)];
            $status = $statuses[array_rand($statuses)];

            DB::table('stock_transfers')->insert([
                'business_id' => $business->id,
                'from_warehouse_id' => $pair[0]->id,
                'to_warehouse_id' => $pair[1]->id,
                'product_id' => $product->id,
                'quantity' => mt_rand(5, 50),
                'status' => $status,
                'user_id' => $this->owner->id ?? null,
                'created_at' => now()->subDays(mt_rand(1, 30)),
                'updated_at' => now()->subDays(mt_rand(0, 10)),
            ]);
        }
    }
}
