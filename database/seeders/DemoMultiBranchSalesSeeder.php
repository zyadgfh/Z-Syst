<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoMultiBranchSalesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        if (! Schema::hasTable('sales') || ! Schema::hasTable('sale_items') || ! Schema::hasTable('branches')) {
            return;
        }

        $branches = DB::table('branches')->where('company_id', $company->id)->get();
        $drugs = DB::table('drugs')->where('company_id', $company->id)->get();
        if ($branches->isEmpty() || $drugs->isEmpty()) {
            return;
        }

        $salesColumns = Schema::getColumnListing('sales');
        for ($i = 0; $i < 10; $i++) {
            $branch = $branches->random();
            $saleData = [];
            if (in_array('company_id', $salesColumns)) {
                $saleData['company_id'] = $company->id;
            }
            if (in_array('branch_id', $salesColumns)) {
                $saleData['branch_id'] = $branch->id;
            }
            if (in_array('currency', $salesColumns)) {
                $saleData['currency'] = 'USD';
            }
            $saleData['created_at'] = now();
            $saleData['updated_at'] = now();

            try {
                $saleId = DB::table('sales')->insertGetId($saleData);
            } catch (\Exception $e) {
                continue;
            }

            $item = $drugs->random();
            $productCol = Schema::hasColumn('sale_items', 'drug_id') ? 'drug_id' : (Schema::hasColumn('sale_items', 'product_id') ? 'product_id' : null);
            if (! $productCol) {
                continue;
            }

            $targetId = $item->id;
            if ($productCol === 'product_id' && Schema::hasTable('products')) {
                $product = DB::table('products')->where('barcode', $item->barcode)->first();
                if (! $product) {
                    continue;
                }
                $targetId = $product->id;
            }

            DB::table('sale_items')->insert([
                'sale_id' => $saleId,
                'company_id' => $company->id,
                $productCol => $targetId,
                'branch_id' => $branch->id,
                'quantity' => rand(1, 4),
                'price' => rand(100, 1000) / 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // update sale total if column exists
            $sum = DB::table('sale_items')->where('sale_id', $saleId)->sum(DB::raw('quantity * price'));
            $update = [];
            if (in_array('total', $salesColumns)) {
                $update['total'] = $sum;
            }
            if (! empty($update)) {
                DB::table('sales')->where('id', $saleId)->update($update);
            }
        }
    }
}
