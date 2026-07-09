<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSalesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        if (! Schema::hasTable('sales') || ! Schema::hasTable('sale_items')) {
            return;
        }

        $drugs = DB::table('drugs')->where('company_id', $company->id)->get();
        if ($drugs->isEmpty()) {
            return;
        }

        // Create a few sample sales pointing to available drugs/products
        $salesColumns = Schema::getColumnListing('sales');
        for ($i = 0; $i < 5; $i++) {
            $saleData = [];
            if (in_array('company_id', $salesColumns)) {
                $saleData['company_id'] = $company->id;
            }
            if (in_array('currency', $salesColumns)) {
                $saleData['currency'] = 'USD';
            }
            if (in_array('total', $salesColumns)) {
                $saleData['total'] = 0;
            }
            if (in_array('amount', $salesColumns)) {
                $saleData['amount'] = 0;
            }
            $saleData['created_at'] = now();
            $saleData['updated_at'] = now();

            try {
                $saleId = DB::table('sales')->insertGetId($saleData);
            } catch (\Exception $e) {
                // Try to supply common required fields that may be missing (invoice_number, reference)
                if (Schema::hasColumn('sales', 'invoice_number')) {
                    $saleData['invoice_number'] = 'INV-'.strtoupper(substr(md5(now()), 0, 8));
                }
                if (Schema::hasColumn('sales', 'reference')) {
                    $saleData['reference'] = 'REF-'.strtoupper(substr(md5(now()), 8, 6));
                }

                try {
                    $saleId = DB::table('sales')->insertGetId($saleData);
                } catch (\Exception $e) {
                    // give up for this sale if insertion still fails
                    continue;
                }
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
                'quantity' => rand(1, 5),
                'price' => rand(100, 1000) / 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // update sale total/amount if those columns exist
            $sum = DB::table('sale_items')->where('sale_id', $saleId)->sum(DB::raw('quantity * price'));
            $update = [];
            if (in_array('total', $salesColumns)) {
                $update['total'] = $sum;
            }
            if (in_array('amount', $salesColumns)) {
                $update['amount'] = $sum;
            }
            if (! empty($update)) {
                DB::table('sales')->where('id', $saleId)->update($update);
            }
        }
    }
}
