<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function index(array $filters = [])
    {
        $query = Product::select('id', 'productName', 'productCode', 'purchase_with_tax', 'sales_price')
            ->withSum('stocks', 'productStock')
            ->with(['expiring_item' => function ($query) {
                $query->select('expire_date', 'product_id')
                    ->where('productStock', '>', 0)
                    ->whereNotNull('expire_date');
            }]);

        if (!empty($filters['search'])) {
            $query->where('productName', 'like', '%' . $filters['search'] . '%')
                ->orWhere('productCode', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['expire_date'])) {
            $query->whereHas('stocks', function ($query) use ($filters) {
                $query->whereBetween('expire_date', [today(), $filters['expire_date']]);
            });
        }

        if (!empty($filters['expired']) && $filters['expired'] === 'true') {
            $query->whereHas('stocks', function ($query) {
                $query->where('expire_date', '<', today())
                    ->where('productStock', '>', 0);
            });
        }

        return $query->latest()->paginate(10);
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? app('tenant.company_id');
            
            $product = Product::create([
                'productName' => $data['productName'],
                'category_id' => $data['category_id'],
                'type_id' => $data['type_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'box_size_id' => $data['box_size_id'] ?? null,
                'productCode' => $data['productCode'] ?? null,
                'purchase_without_tax' => $data['purchase_without_tax'] ?? null,
                'purchase_with_tax' => $data['purchase_with_tax'] ?? null,
                'profit_percent' => $data['profit_percent'] ?? null,
                'sales_price' => $data['sales_price'] ?? null,
                'wholesale_price' => $data['wholesale_price'] ?? null,
                'alert_qty' => $data['alert_qty'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'tax_type' => $data['tax_type'] ?? null,
                'images' => $data['images'] ?? null,
                'meta' => $data['meta'] ?? null,
                'company_id' => $companyId,
            ]);

            if (!empty($data['batch_no']) || !empty($data['qty'])) {
                Stock::create([
                    'product_id' => $product->id,
                    'batch_no' => $data['batch_no'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                    'productStock' => $data['qty'] ?? 0,
                    'company_id' => $companyId,
                ]);
            }

            return $product;
        });
    }

    public function update(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            $companyId = $data['company_id'] ?? app('tenant.company_id');

            // Handle images
            if (!empty($data['removed_images'])) {
                $prevImages = array_diff($product->images ?? [], $data['removed_images']);
                foreach ($data['removed_images'] as $image) {
                    if (Storage::exists($image)) {
                        Storage::delete($image);
                    }
                }
                $prevImages = array_values($prevImages);
            } else {
                $prevImages = $product->images ?? [];
            }

            $newImages = $data['images'] ?? [];
            $mergedImages = array_merge($prevImages, $newImages);

            // Update product
            $product->update([
                'productName' => $data['productName'],
                'category_id' => $data['category_id'],
                'type_id' => $data['type_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'box_size_id' => $data['box_size_id'] ?? null,
                'productCode' => $data['productCode'] ?? null,
                'purchase_without_tax' => $data['purchase_without_tax'] ?? null,
                'purchase_with_tax' => $data['purchase_with_tax'] ?? null,
                'profit_percent' => $data['profit_percent'] ?? null,
                'sales_price' => $data['sales_price'] ?? null,
                'wholesale_price' => $data['wholesale_price'] ?? null,
                'alert_qty' => $data['alert_qty'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'tax_type' => $data['tax_type'] ?? null,
                'images' => $mergedImages,
                'meta' => $data['meta'] ?? null,
            ]);

            // Update or create stock
            $stock = Stock::where('product_id', $product->id)->first();

            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                    'productStock' => $stock->productStock + ($data['qty'] ?? 0),
                ]);
            } elseif (!empty($data['batch_no']) || !empty($data['qty'])) {
                Stock::create([
                    'product_id' => $product->id,
                    'batch_no' => $data['batch_no'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                    'productStock' => $data['qty'] ?? 0,
                    'company_id' => $companyId,
                ]);
            }

            return $product->fresh();
        });
    }

    public function delete(Product $product)
    {
        return DB::transaction(function () use ($product) {
            foreach ($product->images ?? [] as $image) {
                if (Storage::exists($image)) {
                    Storage::delete($image);
                }
            }

            $product->delete();
            return true;
        });
    }

    public function updateStock(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            $companyId = $data['company_id'] ?? app('tenant.company_id');

            $product->update([
                'purchase_without_tax' => $data['purchase_without_tax'],
                'purchase_with_tax' => $data['purchase_with_tax'],
                'profit_percent' => $data['profit_percent'] ?? null,
                'sales_price' => $data['sales_price'],
                'wholesale_price' => $data['wholesale_price'],
                'tax_id' => $data['tax_id'] ?? null,
                'tax_type' => $data['tax_type'] ?? null,
            ]);

            $stock = Stock::where('product_id', $product->id)
                ->where('batch_no', $data['batch_no'])
                ->first();

            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'],
                    'expire_date' => $data['expire_date'],
                    'productStock' => $stock->productStock + $data['qty'],
                ]);
            } else {
                Stock::create([
                    'product_id' => $product->id,
                    'batch_no' => $data['batch_no'],
                    'expire_date' => $data['expire_date'],
                    'productStock' => $data['qty'],
                    'company_id' => $companyId,
                ]);
            }

            return $product->fresh();
        });
    }

    public function stocksWithProduct(array $filters = [])
    {
        $query = Stock::select('id', 'expire_date', 'product_id', 'batch_no', 'productStock')
            ->with([
                'product.tax:id,rate,name',
                'product:id,productName,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,tax_id,tax_type,productCode',
            ]);

        if (!empty($filters['search'])) {
            $query->where(function ($subQuery) use ($filters) {
                $subQuery->where('batch_no', 'like', '%' . $filters['search'] . '%')
                    ->orWhereHas('product', function ($query) use ($filters) {
                        $query->where('productName', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('productCode', 'like', '%' . $filters['search'] . '%');
                    });
            });
        }

        if (!empty($filters['check_stock']) && $filters['check_stock'] === 'true') {
            $query->where('productStock', '>', 0);
        }

        return $query->latest()->paginate(10);
    }
}